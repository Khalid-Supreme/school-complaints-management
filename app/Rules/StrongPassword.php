<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Global password-strength policy applied everywhere a password is created,
 * changed or reset. Requires at least 8 characters plus at least one uppercase
 * letter, one lowercase letter, one number and one symbol, and reports each
 * unmet requirement individually so the UI can show precise feedback.
 *
 * This is a single source of truth: registration, password reset, forced and
 * voluntary password changes all funnel through it via ValidationRules.
 */
class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // Presence is governed by the required/sometimes rules.
        }

        $value = (string) $value;

        if (mb_strlen($value) < 8) {
            $fail('The :attribute must be at least 8 characters.');
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('The :attribute must contain at least one number.');
        }

        if (! preg_match('/[^A-Za-z0-9\s]/', $value)) {
            $fail('The :attribute must contain at least one special character.');
        }
    }
}
