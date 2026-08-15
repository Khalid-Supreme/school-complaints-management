<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Generates and distributes one-time temporary passwords for users created
 * or reset by an administrator. The plaintext is never persisted or logged:
 * only bcrypt(sha256(temp)) is stored so the client-side SHA-256 login flow
 * continues to work unchanged.
 */
class PasswordResetService
{
    /**
     * Generate a random temporary password that satisfies the global password
     * policy: at least 8 characters with at least one uppercase letter, one
     * lowercase letter, one number and one symbol. Ambiguous characters
     * (0/O, 1/l/I) are excluded so the emailed password is easy to type. The
     * plaintext is never stored or logged.
     */
    public function generateTemporaryPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%^&*()-_=+';

        // Guarantee one character from every required class...
        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        // ...then fill the rest from the combined pool.
        $pool = $upper.$lower.$digits.$symbols;
        $poolLength = strlen($pool);

        for ($i = 0; $i < 12; $i++) {
            $chars[] = $pool[random_int(0, $poolLength - 1)];
        }

        return $this->secureShuffle($chars);
    }

    /**
     * Cryptographically-secure Fisher-Yates shuffle (str_shuffle is not
     * guaranteed to be CSPRNG-backed).
     *
     * @param  array<int, string>  $chars
     */
    protected function secureShuffle(array $chars): string
    {
        $count = count($chars);

        for ($i = $count - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    /**
     * Persist a temporary password for the account owner. Pure database work:
     * the plaintext is returned so the caller can decide how and when to
     * deliver it (email is dispatched separately and fail-open).
     *
     * @return string The plaintext temporary password (email-only, never stored).
     */
    public function applyTemporaryPassword(User $user, ?string $temporaryPassword = null): string
    {
        $temporaryPassword = $temporaryPassword ?? $this->generateTemporaryPassword();

        $user->password = hash('sha256', $temporaryPassword);
        $user->must_change_password = true;
        $user->password_change_pending_verification = false;
        $user->save();

        return $temporaryPassword;
    }

    /**
     * Email the temporary password to the account owner.
     *
     * Never throws: a delivery failure is logged (without the password) and
     * the caller proceeds. The account already exists in the database when
     * this runs, so a transport failure must never surface as a request error
     * or hide the fact that the account was created — the administrator can
     * simply use "Reset Password" to re-issue credentials.
     *
     * @return bool Whether the email was accepted by the transport.
     */
    public function sendTemporaryPassword(User $user, string $temporaryPassword): bool
    {
        try {
            Mail::send('emails.admin-password-reset', [
                'user' => $user,
                'temporary_password' => $temporaryPassword,
                'frontend_url' => rtrim((string) config('app.frontend_url'), '/'),
            ], function ($message) use ($user) {
                $message->to($user->email, $user->full_name)
                    ->subject('Your temporary password');
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('Temporary password email failed to send', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
