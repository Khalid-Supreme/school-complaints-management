<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Complaints Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum number of complaints a single user may submit per day.
    'daily_limit' => (int) env('COMPLAINTS_DAILY_LIMIT', 2),
];