<?php

return [
    // paths will be computed at runtime
    'paths' => [],

    'cache' => env('LARAVEL_BLADE_CLI_CACHE', true),

    'compiled' => get_cached_path(),
];
