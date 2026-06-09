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
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->recordLoginAttempt($credentials['email'], $request, false, 'Invalid credentials');
            
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (!$user->is_active) {
            $this->recordLoginAttempt($credentials['email'], $request, false, 'Account inactive');
            
            throw ValidationException::withMessages([
                'email' => __('Account is inactive.'),
            ]);
        }

        $this->recordLoginAttempt($credentials['email'], $request, true);
        $this->userRepository->updateLastLogin($user);

        // For SPA using Sanctum, token might not be needed if session-based, 
        // but returning standard structure if using tokens.
        // We will assume stateful SPA authentication based on the design,
        // so token generation isn't strictly needed, but we provide it just in case.
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
    protected function recordLoginAttempt(string $email, Request $request, bool $successful, ?string $reason = null): void
    {
        LoginAttempt::create([
            'user_id' => User::where('email', $email)->value('id'),
            'email' => $email,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'successful' => $successful,
            'failure_reason' => $reason,
        ]);
    }
}
