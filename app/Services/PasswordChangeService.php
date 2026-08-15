<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\PasswordChangeVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * One-time code email verification for password changes. After the user
 * selects a new password the change is NOT final until they prove control
 * of the account email by entering a 6-digit code. Only the hash of the code
 * is stored; the plaintext code is sent only to the account email address.
 *
 * State invariant: a user is only ever in the "pending verification" state
 * when a valid, active challenge exists for them. Challenge rows are created
 * inside the same database transaction as the password update, and emails are
 * dispatched afterwards in a fail-open manner so a delivery failure can never
 * strand the user without a usable challenge (they can always resend).
 */
class PasswordChangeService
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public const CODE_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    /**
     * Persist a fresh challenge for the user, invalidating any earlier ones.
     * Pure database work (no email/audit) so callers can run it inside a
     * transaction alongside the password update.
     *
     * @return string The plaintext 6-digit code (email-only, never stored).
     */
    public function createChallenge(User $user): string
    {
        $user->passwordChangeVerifications()->delete();

        $code = $this->generateCode();

        PasswordChangeVerification::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
        ]);

        return $code;
    }

    /**
     * Resend a fresh verification code. Only valid while a pending
     * verification flow exists for the account.
     *
     * @return array{status: string, delivered: bool}
     */
    public function resend(User $user): array
    {
        if (! $user->password_change_pending_verification) {
            return ['status' => 'no_flow', 'delivered' => false];
        }

        $code = $this->createChallenge($user);
        $delivered = $this->sendVerificationEmail($user, $code);

        $this->auditLogger->log(AuditAction::PasswordChangeCodeSent, $user, null, 'Password change verification code resent', [
            'resent' => true,
            'delivered' => $delivered,
        ]);

        return ['status' => 'ok', 'delivered' => $delivered];
    }

    /**
     * Verify a submitted code against the user's active challenge.
     *
     * @return array{status: string, remaining_attempts?: int}
     */
    public function verify(User $user, string $code): array
    {
        $challenge = $user->passwordChangeVerifications()->latest('id')->first();

        if (! $challenge) {
            return ['status' => 'no_challenge'];
        }

        if ($challenge->isUsed()) {
            return ['status' => 'used'];
        }

        if ($challenge->attempts >= (int) $challenge->max_attempts) {
            // Invalidate the challenge so further guessing is impossible; the
            // user stays gated and must obtain a fresh code via Resend.
            $challenge->delete();
            $this->auditLogger->log(AuditAction::PasswordChangeVerificationFailed, $user, null, 'Password change verification failed: attempts exhausted');

            return ['status' => 'exhausted'];
        }

        if ($challenge->isExpired()) {
            $this->auditLogger->log(AuditAction::PasswordChangeVerificationExpired, $user, null, 'Password change verification failed: code expired');

            return ['status' => 'expired'];
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->consumeAttempt();

            $this->auditLogger->log(AuditAction::PasswordChangeVerificationFailed, $user, null, 'Password change verification failed: invalid code', [
                'remaining_attempts' => $challenge->attemptsRemaining(),
            ]);

            return [
                'status' => 'invalid',
                'remaining_attempts' => $challenge->attemptsRemaining(),
            ];
        }

        $challenge->used_at = now();
        $challenge->save();

        $user->password_change_pending_verification = false;
        $user->save();

        $this->auditLogger->log(AuditAction::PasswordChangeVerified, $user, null, 'Password change verified');

        return ['status' => 'verified'];
    }

    /**
     * Current verification-flow state for the authenticated user. The backend
     * is authoritative; the frontend must never assume a valid flow based on
     * local state alone.
     *
     * @return array<string, mixed>
     */
    public function status(User $user): array
    {
        $challenge = $user->passwordChangeVerifications()->latest('id')->first();

        $hasChallenge = $challenge !== null;

        return [
            'pending' => (bool) $user->password_change_pending_verification,
            'masked_email' => AuthService::maskEmail($user->email),
            'has_challenge' => $hasChallenge,
            'used' => $hasChallenge && $challenge->isUsed(),
            'expired' => $hasChallenge && $challenge->isExpired(),
            'exhausted' => $hasChallenge && $challenge->attempts >= (int) $challenge->max_attempts,
            'attempts_remaining' => $hasChallenge ? $challenge->attemptsRemaining() : 0,
        ];
    }

    /**
     * Email the combined password-change notification + verification code to
     * the account owner. Sending a single consolidated email avoids dispatching
     * two back-to-back messages, which was the root cause of the verification
     * code email silently failing against the mail transport while the
     * notification email succeeded (dispatch is fail-open by design).
     *
     * Never throws: a delivery failure is logged (without the code) and the
     * caller proceeds, because the challenge already exists in the database and
     * the user can resend.
     *
     * @return bool Whether the email was accepted by the transport.
     */
    public function sendVerificationEmail(User $user, string $code): bool
    {
        try {
            Mail::send('emails.password-changed', [
                'user' => $user,
                'code' => $code,
                'expires_in_minutes' => self::CODE_TTL_MINUTES,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->full_name)
                    ->subject('Your password has been changed');
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('Password change verification email failed to send', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate a 6-digit numeric one-time code.
     */
    protected function generateCode(): string
    {
        return Str::padLeft((string) random_int(0, 999999), 6, '0');
    }
}
