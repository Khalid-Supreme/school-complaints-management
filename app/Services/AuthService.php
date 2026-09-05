<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\TransientToken;

class AuthService
{
    protected UserRepository $userRepository;

    protected AuditLogger $auditLogger;

    public function __construct(UserRepository $userRepository, AuditLogger $auditLogger)
    {
        $this->userRepository = $userRepository;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Authenticate user and issue token (or session if SPA).
     *
     * @throws ValidationException
     */
    public function login(array $credentials, Request $request): array
    {
        $username = $credentials['username'];
        $user = User::where('institution_id', $username)->first();

        // Password is transmitted in plaintext over HTTPS; pre-hash with SHA-256
        // to match the existing storage format bcrypt(sha256(password)) and
        // preserve all existing password hashes without migration.
        if (! $user || ! Hash::check(hash('sha256', $credentials['password']), $user->password)) {
            $this->recordLoginAttempt($username, $request, false, 'Invalid credentials');
            $this->auditLogger->log(AuditAction::LoginFailed, null, null, 'Failed login attempt', [
                'username' => Str::limit($username, 150, ''),
            ]);

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            $this->recordLoginAttempt($username, $request, false, 'Account inactive');
            $this->auditLogger->log(AuditAction::LoginFailed, $user, null, 'Login attempt on inactive account');

            throw ValidationException::withMessages([
                'username' => __('Account is inactive.'),
            ]);
        }

        if (is_null($user->email_verified_at)) {
            $this->recordLoginAttempt($username, $request, false, 'Email not verified');
            $this->auditLogger->log(AuditAction::LoginFailed, $user, null, 'Login attempt on unverified account');

            throw ValidationException::withMessages([
                'username' => __('Please verify your email address :email before logging in. Check inbox for the verification email.', [
                    'email' => self::maskEmail($user->email),
                ]),
            ]);
        }

        $this->recordLoginAttempt($username, $request, true);
        $this->userRepository->updateLastLogin($user);
        $this->auditLogger->log(AuditAction::Login, $user, null, 'User logged in');

        if ($user->must_change_password) {
            $this->auditLogger->log(AuditAction::PasswordChangeTemporaryLogin, $user, null, 'Temporary password used to log in');
        }

        // For SPA using Sanctum, token might not be needed if session-based,
        // but returning standard structure if using tokens.
        // We will assume stateful SPA authentication based on the design,
        // so token generation isn't strictly needed, but we provide it just in case.
        /**
         * Creates an authentication token for the user and retrieves its plain text representation.
         *
         * This line generates a new API token for the authenticated user using Laravel's
         * built-in token generation system (likely Sanctum). The token is assigned the name
         * 'auth_token' for identification purposes.
         *
         * @return string The plain text representation of the generated authentication token.
         *                This token can be used for subsequent API requests to authenticate
         *                the user without requiring their password.
         */
        $expirationMinutes = (int) config('sanctum.expiration');
        $expiresAt = $expirationMinutes > 0 ? now()->addMinutes($expirationMinutes) : null;
        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return [
            'user' => $user->load('role'),
            'token' => $token,
        ];
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): void
    {
        // For SPA stateful authentication:
        // auth()->guard('web')->logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        // If using token:
        $user = $request->user();
        if ($user) {
            $this->auditLogger->log(AuditAction::Logout, $user, null, 'User logged out');

            $token = $user->currentAccessToken();
            if ($token && ! $token instanceof TransientToken) {
                $token->delete();
            }
        }
    }

    /**
     * Record a login attempt.
     *
     * @param  string  $email
     */
    protected function recordLoginAttempt(string $username, Request $request, bool $successful, ?string $reason = null): void
    {
        $user = User::where('institution_id', $username)->first();
        LoginAttempt::create([
            'user_id' => $user?->id,
            // Align the unknown-username value with the login_attempts.email column (150).
            'email' => $user?->email ?? Str::limit($username, 150, ''),
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'successful' => $successful,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mask an email address for display (e.g. "ri********@gmail.com").
     * Shared with the password-change verification flow so masking is
     * consistent across auth surfaces. The local part keeps its first two
     * characters; the domain is preserved for recognition.
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 2) {
            $visible = $name;
            $masked = '';
        } else {
            $visible = substr($name, 0, 2);
            $masked = str_repeat('*', strlen($name) - 2);
        }

        return $visible.$masked.'@'.$domain;
    }
}
