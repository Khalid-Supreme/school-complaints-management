<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class AesEncryptionService
{
    /**
     * Encrypt a string using Laravel's built-in AES encryption.
     *
     * @param string $value
     * @return string
     */
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a previously encrypted string.
     *
     * @param string $payload
     * @return string|null
     */
    public function decrypt(string $payload): ?string
    {
        try {
            return Crypt::decryptString($payload);
        } catch (DecryptException $e) {
            // Log the error if necessary
            return null;
        }
    }
}
