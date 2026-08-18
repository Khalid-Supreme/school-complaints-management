<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Complaints Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum number of complaints a single user may submit per day.
    'daily_limit' => (int) env('COMPLAINTS_DAILY_LIMIT', 10),

    // Maximum number of additional attachments a complainant may upload after
    // a complaint has been submitted (on top of any uploaded at submission).
    'extra_attachment_limit' => (int) env('COMPLAINTS_EXTRA_ATTACHMENT_LIMIT', 1),

    // Grace window (minutes) after submission within which an attachment is
    // treated as part of the initial submission rather than an extra upload.
    'extra_attachment_grace_minutes' => (int) env('COMPLAINTS_EXTRA_ATTACHMENT_GRACE', 10),
];