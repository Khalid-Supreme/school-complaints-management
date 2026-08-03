<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Authenticate user and issue token (or session if SPA).
     *
     * @throws ValidationException
     */
    public function login(array $credentials, Request $request): array
    {
        $username = $credentials['username'];
        $user = User::where('email', $username)
            ->orWhere('institution_id', $username)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->recordLoginAttempt($username, $request, false, 'Invalid credentials');

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            $this->recordLoginAttempt($username, $request, false, 'Account inactive');

            throw ValidationException::withMessages([
                'username' => __('Account is inactive.'),
            ]);
        }

        if (is_null($user->email_verified_at)) {
            $this->recordLoginAttempt($username, $request, false, 'Email not verified');

            throw ValidationException::withMessages([
                'username' => __('Please verify your email address :email before logging in.', [
                    'email' => $this->maskEmail($user->email),
                ]),
            ]);
        }

        $this->recordLoginAttempt($username, $request, true);
        $this->userRepository->updateLastLogin($user);

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
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
    }

    /**
     * Record a login attempt.
     *
     * @param  string  $email
     */
    protected function recordLoginAttempt(string $username, Request $request, bool $successful, ?string $reason = null): void
    {
        $user = User::where('email', $username)->orWhere('institution_id', $username)->first();
        LoginAttempt::create([
            'user_id' => $user?->id,
            'email' => $user?->email ?? $username,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'successful' => $successful,
            'failure_reason' => $reason,
        ]);
    }

    protected function maskEmail(string $email): string
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
