<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

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
     * @param array $credentials
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function login(array $credentials, Request $request): array
    {
        $username = $credentials['username'];
        $user = User::where('email', $username)
            ->orWhere('institution_id', $username)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->recordLoginAttempt($username, $request, false, 'Invalid credentials');
            
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        if (!$user->is_active) {
            $this->recordLoginAttempt($username, $request, false, 'Account inactive');
            
            throw ValidationException::withMessages([
                'username' => __('Account is inactive.'),
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
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load('role'),
            'token' => $token,
        ];
    }

    /**
     * Logout user.
     *
     * @param Request $request
     * @return void
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
     * @param string $email
     * @param Request $request
     * @param bool $successful
     * @param string|null $reason
     * @return void
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
}
