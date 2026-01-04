<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Uplinkr
    |--------------------------------------------------------------------------
    |
    | Still to be described
    |
    */
    'storage' => [

        /*
        |--------------------------------------------------------------------------
        | CAPTION
        |--------------------------------------------------------------------------
        |
        | TODO Check if driver is still needed or can be removed
        |
        */
        'disk' => 'local', # local|...

        /*
        |--------------------------------------------------------------------------
        | Main Storage Path
        |--------------------------------------------------------------------------
        |
        | Still to be described
        |
        */
        'path' => 'uplinkr',
        'probe_results' => 'probes',
        'probe_filename_separator' => '@',

        /*
        |--------------------------------------------------------------------------
        | CAPTION
        |--------------------------------------------------------------------------
        |
        | Still to be described
        |
        */
        'file_extension' => 'json',

        /*
        |--------------------------------------------------------------------------
        | CAPTION
        |--------------------------------------------------------------------------
        |
        | Still to be described
        |
        */
        'archive_folder' => 'archived',

        /*
        |--------------------------------------------------------------------------
        | Allow complete deletion of all data
        |--------------------------------------------------------------------------
        |
        | Still to be described
        |
        */
        'allow_complete_wipe' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Probes
    |--------------------------------------------------------------------------
    |
    | Still to be described
    |
    */
    'probes' => [

        /*
        |--------------------------------------------------------------------------
        | Standard latency for probe requests
        |--------------------------------------------------------------------------
        |
        | Still to be described
        |
        */
        'standard_latency' => 1500,


    ],

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    |
    | Still to be described
    |
    */
    'projects' => [

        /*
        |--------------------------------------------------------------------------
        | Standard Project Name
        |--------------------------------------------------------------------------
        |
        | Still to be described
        |
        */
        'standard_project' => 'standard_project',
        'standard_project_status' => 'enabled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Still to be described
    |
    */
    'logging' => [
        [
            /*
             * Name of the log channel Uplinkr should use.
             */
            'log_channel' => env('UPLINKR_LOG_CHANNEL', 'uplinkr'),

            /*
             * Default channel definition (used if the host app doesn't already have it).
             */
            'log' => [
                'driver' => env('UPLINKR_LOG_DRIVER', 'daily'), // daily|single|stack|...
                'path' => env('UPLINKR_LOG_PATH', storage_path('logs/uplinkr.log')),
                'level' => env('UPLINKR_LOG_LEVEL', 'info'),
                'days' => (int)env('UPLINKR_LOG_DAYS', 14),
                'replace_placeholders' => true,
            ],
        ],
    ],
];
