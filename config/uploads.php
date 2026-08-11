<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the file-upload security policy consumed by the
    | attachment Form Request and the upload service. Every upload must pass
    | through this policy; keep these lists in sync with any UI accept tips.
    |
    */

    'attachments' => [
        // Disk used to persist complaint attachments. This MUST NOT be a
        // webroot-mounted disk: files are stored outside `public/` and served
        // only through the authenticated download controller, which prevents
        // direct execution of uploaded content.
        'disk' => env('UPLOADS_DISK', 'local'),

        // Maximum accepted size in kilobytes (10 MB).
        'max_size_kb' => (int) env('UPLOADS_MAX_SIZE_KB', 10240),

        // Allowed file extensions (lowercase, no leading dot).
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'pdf',
            'doc',
            'docx',
            'txt',
        ],

        // Allowed MIME types (server-detected, not client-declared).
        // Executables, scripts and archives are intentionally absent.
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ],

        'images' => [
            // Image pixel caps. Only evaluated for files whose detected MIME is
            // an image; oversized or corrupt images are rejected outright to
            // prevent decompression-bomb style resource exhaustion.
            'max_width' => (int) env('UPLOADS_IMAGE_MAX_WIDTH', 8000),
            'max_height' => (int) env('UPLOADS_IMAGE_MAX_HEIGHT', 8000),
            'max_pixels' => (int) env('UPLOADS_IMAGE_MAX_PIXELS', 25000000),
        ],
    ],
];
