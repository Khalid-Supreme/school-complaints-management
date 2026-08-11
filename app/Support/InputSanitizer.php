<?php

namespace App\Support;

class InputSanitizer
{
    /**
     * Sanitize free-form text stored by the application.
     *
     * Removes active content (script/style/iframe/object/embed/link/meta blocks),
     * inline event handlers and javascript:/data:/vbscript: URIs. Plain text is
     * preserved, so legitimate complaint and chat content is unaffected.
     */
    public static function clean(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = (string) $value;

        // Strip full tag blocks (including their content) for active elements.
        $value = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1\s*>#is', '', $value) ?? $value;
        // Remove any remaining active element tags (self-closed / unclosed).
        $value = preg_replace('#</?(script|style|iframe|object|embed|link|meta)\b[^>]*>#i', '', $value) ?? $value;

        // Remove inline event handlers such as onload="...".
        $value = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? $value;

        // Neutralize dangerous URI schemes in href/src/srcset attributes.
        $value = preg_replace('/(\s(?:href|src|srcset|action)\s*=\s*["\']?)\s*(javascript|data|vbscript)\s*:/i', '$1$2:', $value) ?? $value;

        // Strip control characters (except tab, newline and carriage return) so
        // null bytes and other invisible control bytes are never stored.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? $value;

        // Normalize whitespace.
        $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\n{3,}/', "\n\n", $value) ?? $value;

        return trim($value);
    }
}
