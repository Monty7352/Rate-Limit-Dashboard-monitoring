<?php

return [
    /*
    | Dashboard Path
    */
    'path' => env('RATE_LIMIT_DASHBOARD_PATH', 'rate-limit-dashboard'),

    /*
    | Dashboard Access Middleware
    */
    'middleware' => ['web'],

    /*
    | Cache retention in seconds (Default: 1 Hour)
    */
    'cache_ttl' => 3600,
];