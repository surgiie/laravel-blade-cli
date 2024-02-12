<?php

return [

    'directories' => [

        /*
         * Here you can specify which directories need to be cleanup. All files older than
         * the specified amount of minutes will be deleted.
         */

        get_cached_path() => [
            'deleteAllOlderThanMinutes' => 60 * 24,
        ],

    ],

    /*
     * The policy class that determines what is deleted.
     */
    'cleanup_policy' => \App\Support\CacheCleanupPolicy::class,
];
