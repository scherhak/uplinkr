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
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Defines how Uplinkr sends alerts when probe results indicate problems.
    | Channels can be enabled/disabled and configured independently.
    |
    */
    'notifications' => [

        /*
        |--------------------------------------------------------------------------
        | Enabled Channels (optional convenience)
        |--------------------------------------------------------------------------
        |
        | If you prefer, you can enable channels via env.
        | Otherwise, each channel has its own "enabled" flag below.
        |
        */
        'enabled' => array_filter(explode(',', (string) env('UPLINKR_NOTIFY_CHANNELS', 'log'))),

        /*
        |--------------------------------------------------------------------------
        | Channels
        |--------------------------------------------------------------------------
        */
        'channels' => [

            /*
            |--------------------------------------------------------------
            | Log Channel
            |--------------------------------------------------------------
            */
            'log' => [
                'enabled' => (bool) env('UPLINKR_NOTIFY_LOG_ENABLED', true),

                // Map to your existing uplinkr logging channel if you want
                'channel' => env('UPLINKR_NOTIFY_LOG_CHANNEL', 'uplinkr'),

                // Level used for the emitted alert log entry
                'level' => env('UPLINKR_NOTIFY_LOG_LEVEL', 'warning'),
            ],

            /*
            |--------------------------------------------------------------
            | Mail Channel
            |--------------------------------------------------------------
            */
            'mail' => [
                'enabled' => (bool) env('UPLINKR_NOTIFY_MAIL_ENABLED', false),

                // Laravel mailer name (config/mail.php)
                'mailer' => env('UPLINKR_NOTIFY_MAIL_MAILER', null),

                // Comma-separated list in env, parsed to array
                'to' => array_filter(explode(',', (string) env('UPLINKR_NOTIFY_MAIL_TO', ''))),

                // Optional: from override
                'from' => [
                    'address' => env('UPLINKR_NOTIFY_MAIL_FROM_ADDRESS', null),
                    'name' => env('UPLINKR_NOTIFY_MAIL_FROM_NAME', null),
                ],

                'subject_prefix' => env('UPLINKR_NOTIFY_MAIL_SUBJECT_PREFIX', '[Uplinkr]'),
            ],

            /*
            |--------------------------------------------------------------
            | Webhook Channel (recommended name instead of "api")
            |--------------------------------------------------------------
            |
            | Send alert data to any external application/endpoint.
            | This is the "bring your own integration" channel.
            |
            */
            'webhook' => [
                'enabled' => (bool) env('UPLINKR_NOTIFY_WEBHOOK_ENABLED', false),

                'url' => env('UPLINKR_NOTIFY_WEBHOOK_URL', null),
                'method' => env('UPLINKR_NOTIFY_WEBHOOK_METHOD', 'POST'),

                // HTTP client behavior
                'timeout_seconds' => (int) env('UPLINKR_NOTIFY_WEBHOOK_TIMEOUT', 10),
                'connect_timeout_seconds' => (int) env('UPLINKR_NOTIFY_WEBHOOK_CONNECT_TIMEOUT', 5),
                'verify_tls' => (bool) env('UPLINKR_NOTIFY_WEBHOOK_VERIFY_TLS', true),

                // Extra headers (JSON in env is nice, but keep it simple here)
                'headers' => [
                    'Content-Type' => 'application/json',
                    // Example:
                    // 'Authorization' => 'Bearer ' . env('UPLINKR_NOTIFY_WEBHOOK_TOKEN'),
                ],

                /*
                 | Retry Strategy
                 | - max_attempts: total attempts including the first call
                 | - backoff_ms: delays between retries
                 */
                'retry' => [
                    'max_attempts' => (int) env('UPLINKR_NOTIFY_WEBHOOK_RETRY_MAX', 3),
                    'backoff_ms' => [0, 2000, 10000],
                ],

                /*
                 | Optional signing (HMAC) so the receiver can verify origin
                 | Example header: X-Uplinkr-Signature: sha256=...
                 */
                'signing' => [
                    'enabled' => (bool) env('UPLINKR_NOTIFY_WEBHOOK_SIGNING', false),
                    'secret' => env('UPLINKR_NOTIFY_WEBHOOK_SECRET', null),
                    'header' => env('UPLINKR_NOTIFY_WEBHOOK_SIGNATURE_HEADER', 'X-Uplinkr-Signature'),
                    'algo' => env('UPLINKR_NOTIFY_WEBHOOK_SIGNATURE_ALGO', 'sha256'),
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Payload Format
        |--------------------------------------------------------------------------
        |
        | You can version your payload to keep it stable over time.
        |
        */
        'payload' => [
            'version' => env('UPLINKR_NOTIFY_PAYLOAD_VERSION', 'uplinkr.v1'),
        ],
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
