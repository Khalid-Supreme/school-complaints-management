<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects files that claim to be images but are corrupt, and image files whose
 * dimensions exceed the configured caps.
 *
 * Only evaluated when the server-detected MIME is an image. Uses getimagesize()
 * (bundled with PHP; GD/Imagick not required) so the pixel geometry is derived
 * from the file bytes, never from the client filename or declared type.
 */
class SafeImage implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || $value->isValid() === false) {
            $fail('The :attribute file is not a valid upload.');

            return;
        }

        $isImage = str_starts_with((string) $value->getMimeType(), 'image/');

        if (! $isImage) {
            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path) || ! is_file($path)) {
            $fail('The :attribute file could not be read.');

            return;
        }

        $size = @getimagesize($path);

        if ($size === false) {
            $fail('The :attribute file is not a valid image.');

            return;
        }

        [$width, $height] = $size;

        if ($width > (int) config('uploads.attachments.images.max_width')
            || $height > (int) config('uploads.attachments.images.max_height')) {
            $fail('The :attribute image dimensions are too large.');

            return;
        }

        if ($width * $height > (int) config('uploads.attachments.images.max_pixels')) {
            $fail('The :attribute image resolution is too large.');
        }
    }
}
