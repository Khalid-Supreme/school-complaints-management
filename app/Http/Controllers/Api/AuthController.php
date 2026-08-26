<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordChangeVerifyRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\PasswordResetSuccessNotification;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\PasswordChangeService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;

    protected AuditLogger $auditLogger;

    protected PasswordChangeService $passwordChangeService;

    public function __construct(AuthService $authService, AuditLogger $auditLogger, PasswordChangeService $passwordChangeService)
    {
        $this->authService = $authService;
        $this->auditLogger = $auditLogger;
        $this->passwordChangeService = $passwordChangeService;
    }

    /**
     * Handle incoming login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated(), $request);

        return response()->json([
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    /**
     * Handle incoming logout request.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('role')
        );
    }

    /**
     * Change the authenticated user's password (used for forced password
     * change on first login and voluntary changes). The password update and
     * the verification challenge are committed atomically; the emails are
     * dispatched afterwards in a fail-open manner so a delivery failure can
     * never leave the account pending verification without a usable code.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Forced change: the user already authenticated with the temporary
        // password this session, so the current password is not re-verified.
        // Voluntary changes still require proof of the current password.
        if (! $user->must_change_password && ! Hash::check(hash('sha256', (string) $request->input('current_password')), $user->password)) {
            $this->auditLogger->log(AuditAction::PasswordChangeFailed, $user, null, 'Password change failed: current password incorrect');

            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        try {
            // Password + challenge update is atomic: either both happen or the
            // account is left exactly as it was (still forced to change).
            $code = DB::transaction(function () use ($request, $user) {
                // The client sends the plaintext new password (server-side
                // validation requires the raw value). Pre-hash with SHA-256 to
                // match the login flow; the 'hashed' cast bcrypts it on save.
                $user->password = hash('sha256', $request->input('password'));
                $user->must_change_password = false;
                $user->password_change_pending_verification = true;
                $user->save();

                return $this->passwordChangeService->createChallenge($user);
            });
        } catch (\Throwable $e) {
            Log::error('Password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->auditLogger->log(AuditAction::PasswordChangeFailed, $user, null, 'Password change failed');

            return response()->json([
                'message' => 'We couldn\'t complete your password change. Please try again.',
            ], 500);
        }

        $this->auditLogger->log(AuditAction::PasswordChange, $user, null, 'Password changed');

        // A single consolidated email carries the password-change notification
        // AND the verification code (with expiry and security warning), so only
        // one message is dispatched after the commit. It never throws back to
        // the client: if delivery fails the challenge already exists, so the
        // user can use Resend to obtain a new code without being stranded.
        $delivered = $this->passwordChangeService->sendVerificationEmail($user, $code);

        $this->auditLogger->log(AuditAction::PasswordChangeCodeSent, $user, null, 'Password change verification code sent', [
            'delivered' => $delivered,
        ]);

        return response()->json([
            'message' => 'Password changed. Please verify the one-time code sent to your email to finish.',
            'password_change_pending_verification' => true,
        ]);
    }

    /**
     * Current password-change verification state for the authenticated user,
     * including a masked email for display. Used on page load/refresh so the
     * frontend never assumes a valid flow based on local state alone.
     */
    public function passwordChangeStatus(Request $request): JsonResponse
    {
        return response()->json(
            $this->passwordChangeService->status($request->user())
        );
    }

    /**
     * Verify the one-time code that was emailed after a password change.
     * Only reachable while the account is gated by the pending flag.
     */
    public function verifyPasswordChange(PasswordChangeVerifyRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->passwordChangeService->verify($user, $request->input('code'));

        return match ($result['status']) {
            'verified' => response()->json([
                'message' => 'Password change verified successfully.',
                'user' => $user->load('role'),
            ]),
            'invalid' => throw ValidationException::withMessages([
                'code' => ['The verification code is incorrect. '.$result['remaining_attempts'].' attempt'.($result['remaining_attempts'] === 1 ? '' : 's').' remaining.'],
            ]),
            'exhausted' => throw ValidationException::withMessages([
                'code' => ['Too many incorrect attempts. Please request a new code.'],
            ]),
            'expired' => throw ValidationException::withMessages([
                'code' => ['This code has expired. Please request a new one.'],
            ]),
            'used' => throw ValidationException::withMessages([
                'code' => ['This code has already been used.'],
            ]),
            default => throw ValidationException::withMessages([
                'code' => ['No active verification code was found. Please request a new one.'],
            ]),
        };
    }

    /**
     * Re-send the one-time verification code after a password change. Issues
     * a fresh code (the previous one immediately becomes invalid) and emails
     * it. Only valid while the account is pending verification.
     */
    public function resendPasswordChange(Request $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->passwordChangeService->resend($user);

        if ($result['status'] === 'no_flow') {
            return response()->json([
                'message' => 'There is no pending password change verification for this account.',
            ], 422);
        }

        return response()->json([
            'message' => 'A new verification code has been sent to your email.',
        ]);
    }

    /**
     * Send a password reset link to the given user.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        $user = User::where('email', $request->input('email'))->first();
        $this->auditLogger->log(
            AuditAction::PasswordResetLink,
            $user,
            null,
            'Password reset link requested'
        );

        return response()->json([
            'message' => 'If an account with that email exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // The client sends the plaintext password (server-side
                // validation requires the raw value); store bcrypt(sha256(raw))
                // so the client-side SHA-256 login flow verifies correctly.
                $user->forceFill([
                    'password' => Hash::make(hash('sha256', $password)),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user = User::where('email', $request->input('email'))->first();
            $this->auditLogger->log(AuditAction::PasswordReset, $user, null, 'Password reset via reset link');

            // Send confirmation email (fail-open: email failure must not break the reset response)
            if ($user) {
                try {
                    $user->notify(new PasswordResetSuccessNotification());
                } catch (\Throwable $e) {
                    Log::warning('Failed to send password-reset success email.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'message' => 'Password has been reset successfully. A confirmation email has been sent.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
