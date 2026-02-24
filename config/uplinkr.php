<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where and how Uplinkr stores probe results, project settings,
    | and archive data. All paths are relative to the configured disk.
    |
    */
    'storage' => [

        /*
        |--------------------------------------------------------------------------
        | Storage Disk
        |--------------------------------------------------------------------------
        |
        | The Laravel filesystem disk where Uplinkr will store all its data.
        | Must be a valid disk configured in config/filesystems.php.
        | Common values: 'local', 's3', 'public'
        |
        */
        'disk' => env('UPLINKR_STORAGE_DISK', 'local'),

        /*
        |--------------------------------------------------------------------------
        | Storage Paths
        |--------------------------------------------------------------------------
        |
        | path: Base directory where all Uplinkr data is stored
        | probe_results: Subdirectory within each project for probe result files
        | probe_filename_separator: Character separating URL from date in filenames
        |   Example: example_com@2026-01-19.json
        |
        */
        'path' => env('UPLINKR_STORAGE_PATH', 'uplinkr'),
        'probe_results' => env('UPLINKR_STORAGE_PROBE_RESULTS', 'probes'),
        'probe_filename_separator' => env('UPLINKR_PROBE_FILENAME_SEPARATOR', '@'),

        /*
        |--------------------------------------------------------------------------
        | File Extension
        |--------------------------------------------------------------------------
        |
        | The file extension used for all Uplinkr data files (probe results,
        | project settings, state files, etc.). Using 'json' allows for easy
        | inspection and manipulation of stored data.
        |
        */
        'file_extension' => env('UPLINKR_FILE_EXTENSION', 'json'),

        /*
        |--------------------------------------------------------------------------
        | Pretty Print Probe Results
        |--------------------------------------------------------------------------
        |
        | Controls whether probe result JSON files in "<project>/probes" are
        | formatted with indentation. Disable in production to reduce file size
        | and I/O overhead.
        |
        */
        'pretty_print_probe_results' => (bool)env('UPLINKR_STORAGE_PRETTY_PRINT_PROBE_RESULTS', true),

        /*
        |--------------------------------------------------------------------------
        | Archive Folder
        |--------------------------------------------------------------------------
        |
        | Name of the subdirectory where archived projects are stored.
        | Archived projects are moved here instead of being deleted, allowing
        | for later retrieval if needed.
        |
        */
        'archive_folder' => env('UPLINKR_ARCHIVE_FOLDER', 'archived'),

        /*
        |--------------------------------------------------------------------------
        | Allow Complete Wipe
        |--------------------------------------------------------------------------
        |
        | DANGER: When enabled, allows complete deletion of all Uplinkr data
        | including all projects, probe results, and settings. This is a
        | destructive operation that cannot be undone. Keep disabled in
        | production environments.
        |
        */
        'allow_complete_wipe' => env('UPLINKR_ALLOW_COMPLETE_WIPE', false),

        /*
        |--------------------------------------------------------------------------
        | Probe Results Grouping
        |--------------------------------------------------------------------------
        |
        | How probe results should be grouped into files:
        | - 'hourly': example_com@2026-01-19-14.json (high-frequency monitoring)
        | - 'daily': example_com@2026-01-19.json (default, balanced)
        | - 'monthly': example_com@2026-01.json (long-term storage)
        |
        */
        'probe_results_grouping' => env('UPLINKR_PROBE_RESULTS_GROUPING', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Probe Configuration
    |--------------------------------------------------------------------------
    |
    | Settings that control how Uplinkr performs HTTP probes to monitor
    | your endpoints. These affect probe behavior and success criteria.
    |
    */
    'probes' => [

        /*
        |--------------------------------------------------------------------------
        | Execution mode
        |--------------------------------------------------------------------------
        |
        | Defines how URL probes are executed:
        |
        | - 'direct': Probes are executed synchronously within the current request.
        |   This is suitable for low-frequency monitoring or when immediate results
        |   are required. Use this mode for simple setups or when you don't have
        |   a queue worker running.
        |
        | - 'job': Probes are dispatched as queued jobs and executed asynchronously
        |   by queue workers. This is recommended for high-frequency monitoring,
        |   multiple probes, or when you want to avoid blocking the main request.
        |   Requires a properly configured queue connection and running queue workers.
        |
        | Possible values: 'direct', 'job'
        | Default: 'direct'
        |
        */
        'execution_mode' => env('UPLINKR_PROBES_EXECUTION_MODE', 'direct'),

        /*
        |--------------------------------------------------------------------------
        | Queue Connection
        |--------------------------------------------------------------------------
        |
        | The queue connection to use when execution_mode is set to 'job'.
        | This should match one of the connections defined in config/queue.php.
        |
        | Common options:
        | - 'sync': Executes jobs synchronously (useful for local development/testing)
        | - 'database': Uses database as queue driver (simple, no additional services)
        | - 'redis': Uses Redis as queue driver (recommended for production, fast)
        | - 'sqs': Uses Amazon SQS (recommended for AWS deployments)
        | - 'beanstalkd': Uses Beanstalkd queue service
        |
        | Note: Make sure your chosen connection is properly configured in
        | config/queue.php and that queue workers are running:
        |   php artisan queue:work <connection-name>
        |
        | Default: 'sync'
        |
        */
        'queue_connection' => env('UPLINKR_PROBES_QUEUE_CONNECTION', 'sync'),

        /*
        |--------------------------------------------------------------------------
        | Standard Latency Threshold
        |--------------------------------------------------------------------------
        |
        | Maximum acceptable response time in milliseconds. Probes that exceed
        | this threshold are marked as slow/unreachable, even if they return
        | a successful HTTP status code.
        | Default: 1500ms (1.5 seconds)
        |
        */
        'standard_latency' => (int)env('UPLINKR_PROBES_STANDARD_LATENCY', 1500),

        /*
        |--------------------------------------------------------------------------
        | User-Agent Header
        |--------------------------------------------------------------------------
        |
        | The User-Agent string sent with each probe request.
        | Helps identify Uplinkr probes in server logs.
        |
        */
        'user_agent' => env('UPLINKR_PROBES_USER_AGENT', 'uplinkr-monitor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Project Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for project management. Projects are used to organize and
    | group related probes together.
    |
    */
    'projects' => [

        /*
        |--------------------------------------------------------------------------
        | Default Project Settings
        |--------------------------------------------------------------------------
        |
        | standard_project: Fallback project name when no project is specified
        | standard_project_status: Default status for newly created projects
        |   Common values: 'enabled', 'disabled', 'archived'
        |
        */
        'standard_project' => env('UPLINKR_STANDARD_PROJECT', 'standard_project'),
        'standard_project_status' => env('UPLINKR_STANDARD_PROJECT_STATUS', 'enabled'),
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
        'enabled' => array_filter(explode(',', (string)env('UPLINKR_NOTIFY_CHANNELS', 'log'))),

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
                'enabled' => (bool)env('UPLINKR_NOTIFY_LOG_ENABLED', true),

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
                'enabled' => (bool)env('UPLINKR_NOTIFY_MAIL_ENABLED', false),

                // Laravel mailer name (config/mail.php)
                'mailer' => env('UPLINKR_NOTIFY_MAIL_MAILER', null),

                // Comma-separated list in env, parsed to array
                'to' => array_filter(explode(',', (string)env('UPLINKR_NOTIFY_MAIL_TO', ''))),

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
                'enabled' => (bool)env('UPLINKR_NOTIFY_WEBHOOK_ENABLED', false),

                'url' => env('UPLINKR_NOTIFY_WEBHOOK_URL', null),
                'method' => env('UPLINKR_NOTIFY_WEBHOOK_METHOD', 'POST'),

                // HTTP client behavior
                'timeout_seconds' => (int)env('UPLINKR_NOTIFY_WEBHOOK_TIMEOUT', 10),
                'connect_timeout_seconds' => (int)env('UPLINKR_NOTIFY_WEBHOOK_CONNECT_TIMEOUT', 5),
                'verify_tls' => (bool)env('UPLINKR_NOTIFY_WEBHOOK_VERIFY_TLS', true),

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
                    'max_attempts' => (int)env('UPLINKR_NOTIFY_WEBHOOK_RETRY_MAX', 3),
                    'backoff_ms' => [0, 2000, 10000],
                ],

                /*
                 | Optional signing (HMAC) so the receiver can verify origin
                 | Example header: X-Uplinkr-Signature: sha256=...
                 */
                'signing' => [
                    'enabled' => (bool)env('UPLINKR_NOTIFY_WEBHOOK_SIGNING', false),
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
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how Uplinkr writes log messages. Uplinkr uses its own
    | dedicated log channel to keep probe-related logs separate from
    | your application logs.
    |
    */
    'logging' => [
        [
            /*
             * Log Channel Name
             * The name of the log channel Uplinkr should use.
             * This channel will be registered automatically if it doesn't exist.
             */
            'log_channel' => env('UPLINKR_LOG_CHANNEL', 'uplinkr'),

            /*
             * Default Channel Definition
             * Configuration used if the host application doesn't already have
             * an 'uplinkr' channel defined in config/logging.php.
             *
             * driver: Log driver (daily, single, stack, syslog, errorlog)
             * path: Full path to the log file
             * level: Minimum log level (debug, info, notice, warning, error, critical, alert, emergency)
             * days: Number of days to retain daily log files
             */
            'log' => [
                'driver' => env('UPLINKR_LOG_DRIVER', 'daily'),
                'path' => env('UPLINKR_LOG_PATH', storage_path('logs/uplinkr.log')),
                'level' => env('UPLINKR_LOG_LEVEL', 'info'),
                'days' => (int)env('UPLINKR_LOG_DAYS', 14),
                'replace_placeholders' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    |
    | Configure the automatic execution of Uplinkr probes.
    | When enabled, Uplinkr integrates with Laravel's task scheduler
    | to run your monitoring probes at the specified interval.
    |
    */
    'scheduler' => [
        /*
         |--------------------------------------------------------------------------
         | Enable Uplinkr Scheduler
         |--------------------------------------------------------------------------
         |
         | When enabled, Uplinkr will automatically register its monitoring
         | command with Laravel's scheduler.
         |
         */

        'enabled' => false,

        /*
         |--------------------------------------------------------------------------
         | Scheduler Cron Expression
         |--------------------------------------------------------------------------
         */

        'cron' => '* * * * *',

        /*
         |--------------------------------------------------------------------------
         | Alert Decision Cron Expression
         |--------------------------------------------------------------------------
         |
         | Optional separate schedule for alert decisions. This is useful when
         | probes run asynchronously via queue jobs and state updates may arrive
         | after the probe dispatch command has already exited.
         |
         | Default is every 2 minutes to give queued probe jobs time to
         | persist fresh state before alert decisions are evaluated.
         |
         | Set to null to reuse the main scheduler cron expression.
         |
         */
        'alert_cron' => '*/2 * * * *',
    ],
];
