<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CurseForge API Configuration
    |--------------------------------------------------------------------------
    |
    | API key for CurseForge. Required to fetch modpacks from CurseForge.
    | Get your key at: https://console.curseforge.com/
    |
    */
    'curseforge_api_key' => env('CURSEFORGE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | How long to cache API responses (in seconds).
    | Default: 1800 seconds (30 minutes)
    |
    */
    'cache_duration' => env('MODPACKS_CACHE_DURATION', 1800),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout for API requests in seconds.
    |
    */
    'request_timeout' => env('MODPACKS_REQUEST_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Items Per Page
    |--------------------------------------------------------------------------
    |
    | Default number of modpacks to display per page.
    |
    */
    'modpacks_per_page' => env('MODPACKS_PER_PAGE', 20),
];
