# Changelog

All notable changes to this project will be documented in this file.

## [0.3.0] – 2026-03-xx

### Added
- **Global I’m Alive Settings (`settings.json`)**
  - Added global `uplinkr/settings.json` configuration file for heartbeat settings
  - Added new command `uplinkr:settings` to manage I’m alive behavior
  - Supports:
    - enable/disable I’m alive
    - interval in hours (`1-24`)
    - channels (`mail`, `log`, `webhook`)
- **Install Integration for I’m Alive**
  - `uplinkr:install` now supports:
    - `--iam-alive`
    - `--iam-alive-interval-hours=...`
    - `--iam-alive-channels=...`
  - Allows enabling/configuring I’m alive directly during installation

### Changed
- **I’m Alive Scheduler Behavior**
  - I’m alive scheduling now reads from `uplinkr/settings.json` (instead of `config/uplinkr.php`)
  - If `iam_alive.enabled = true` and `uplinkr.scheduler.enabled = true`, `uplinkr:iam-alive` is automatically scheduled
  - Interval is now hour-based (`1-24`) with cron mapping:
    - `1-23`: `0 */N * * *`
    - `24`: `0 0 * * *`
- **I’m Alive Notification Channels**
  - `uplinkr:iam-alive` now supports channel routing like check alerts:
    - `mail` via Laravel mail notifications
    - `log` via `uplinkr-log`
    - `webhook` via `uplinkr-webhook`
  - Mail channel validation remains explicit (requires enabled mail channel + recipients)
- **I’m Alive Notification Content**
  - `uplinkr:iam-alive` now aggregates and sends monitoring summary data across all projects:
    - active projects (`status=enabled`)
    - configured probes/checks (sum of all project `settings.json > probes`)
    - successful checks (current probe state where `consecutive_failures = 0`)
    - failed checks (current probe state where `consecutive_failures > 0`)
  - Mail output now includes a readable summary section and a readable `iam_alive` settings section
  - Webhook and log payloads now include the same summary + settings data for consistent channel behavior
- **I’m Alive Settings Persistence**
  - Added `iam_alive.last_sent_at` in global `uplinkr/settings.json`
  - `last_sent_at` is updated whenever an I’m alive notification is successfully dispatched
  - `uplinkr:settings` now shows `last_sent_at` in command output
- **Scheduler Configuration**
  - Added `scheduler.alert_cron` in `config/uplinkr.php` to allow a separate cron expression for `uplinkr:project:alert:decision`
  - `scheduler.alert_cron` defaults to `*/2 * * * *` to provide a safer delay for async probe execution (`probes.execution_mode = job`)
  - Setting `scheduler.alert_cron` to `null` reuses `scheduler.cron` for backward-compatible scheduling behavior
- **Project List Command Output**
  - Expanded `uplinkr:project:list` output to read project data from `settings.json` and `state.json`
  - Added project header output with `project`, `label`, and status (`enabled`/`disabled`) with colorized status rendering
  - Added optional description line, probes/checks table, alerts table, and state table
  - Alerts are now rendered as a structured table (`enabled`, `trigger_after_failures`, `cooldown_minutes`, `latency_threshold_ms`, `trigger_after_slow`, `channels`)
  - State is now rendered as a structured table (`total_failures`, `last_notification`)
  - Added `--project` option to show only a selected project
  - If `--project` does not match, command now prints a hint and lists all available projects
  - Moved project list CLI output strings into `resources/lang/en/messages.php`

### Fixed
- **I’m Alive Command Config Consistency**
  - `uplinkr:iam-alive` now resolves `UplinkrConfig` at runtime to avoid stale singleton config in test/runtime edge cases
  - Fixes flaky behavior where configured mail recipients could be missed in long-running test processes
- **Scheduler Race Reduction for Async Probe Execution**
  - `uplinkr:project:alert:decision` is no longer scheduled with `runInBackground()`
  - Reduces the risk of alert decisions reading stale `state.json` when probe runs dispatch queued jobs and return before workers persist probe state
- **Project List File Extension Handling**
  - `ListHandler::allWithDetails()` now resolves `settings` and `state` filenames with the configured storage extension (`uplinkr.storage.file_extension`) instead of hardcoded `.json`
  - Fixes `uplinkr:project:list` returning no projects when `UPLINKR_FILE_EXTENSION` is set to a non-`json` value

## [0.2.1] – 2026-02-26

### Changed
- **Scheduler Configuration**
  - Added `scheduler.alert_cron` in `config/uplinkr.php` to allow a separate cron expression for `uplinkr:project:alert:decision`
  - `scheduler.alert_cron` defaults to `*/2 * * * *` to provide a safer delay for async probe execution (`probes.execution_mode = job`)
  - Setting `scheduler.alert_cron` to `null` reuses `scheduler.cron` for backward-compatible scheduling behavior

### Fixed
- **Scheduler Race Reduction for Async Probe Execution**
  - `uplinkr:project:alert:decision` is no longer scheduled with `runInBackground()`
  - Reduces the risk of alert decisions reading stale `state.json` when probe runs dispatch queued jobs and return before workers persist probe state


## [0.2.0] – 2026-02-21

### Added
- **Asynchronous Probe Execution via Jobs**
  - New `ProbeUrl` job class for executing URL probes in queue workers
  - Configurable execution mode via `config/uplinkr.php`:
    - `direct`: Synchronous execution (default, backward compatible)
    - `job`: Asynchronous execution via Laravel queues
  - Queue connection configuration (`probes.queue_connection`)
  - Support for all Laravel queue drivers (sync, database, redis, sqs, beanstalkd)
- **New Command: `uplinkr:config`**
  - Display the current Uplinkr configuration in structured format
  - Similar to Laravel's `php artisan config:show uplinkr` command
  - Hierarchical display of all configuration values
- **TLS Metadata for Probe Results**
  - HTTPS probes now resolve certificate expiration dates
  - New result field: `probe_tls_expiration_date` (ISO-8601 or `null`)
  - Optional per-probe TLS options:
    - `tls.enabled`
    - `tls.timeout`
    - `tls.verify_peer`
    - `tls.verify_peer_name`
    - `tls.allow_self_signed`
    - `tls.peer_name`
    - `tls.cafile`
    - `tls.capath`

### Changed
- **Composer Platform Requirements**
  - Added `ext-openssl` as an explicit Composer requirement to ensure TLS certificate parsing support is available at install time
- **UrlHandler Refactoring**
  - `handle()` method now dispatches jobs when `execution_mode = 'job'`
  - Extracted probe execution logic into new public `executeProbe()` method
  - Returns `null` when dispatching jobs (instead of probe result array)
- **UplinkrConfig Extension**
  - Added `probeExecutionMode` property with getter and helper methods
  - Added `probeQueueConnection` property with getter
  - New convenience method: `shouldExecuteProbesAsJob()`
- **Improved Console Output**
  - `HandlesProbeOutput` trait now handles `null` results gracefully
  - New translation key: `probe_dispatched_as_job`
  - Clear feedback when probes are dispatched to queue vs executed directly
- **Alert Notification Payloads**
  - `probe_tls_expiration_date` is now propagated through alert decisions
  - Included in mail, webhook, and log notifications
- **Project-Level Alert Aggregation**
  - Alert notifications are now grouped per project instead of sending one message per failed probe
  - `AlertDecisionHandler` keeps the existing decision logic (`trigger_after_failures`, cooldown, enabled state) and sends grouped notifications after decision collection
  - Grouping key uses project + alert configuration to keep channel/cooldown semantics intact
- **Alert Notification Rendering**
  - `AlertNotificationHandler` now supports aggregated payloads via `probes[]` while remaining backward compatible with legacy single-probe payloads
  - Aggregated mail notifications now include a compact per-probe list (probe, failure count, TLS value)
  - Aggregated payload probes are sorted by probe name for deterministic output in mail, log, and webhook data
- **Probe Result Serialization**
  - Probe result file formatting is now configurable
  - Pretty printing can be disabled for compact JSON output in high-volume environments
- **Project Handler Structure**
  - Project handlers reorganized into focused namespaces:
    - `Handler\\Project\\Analyze\\...`
    - `Handler\\Project\\Archive\\...`
    - `Handler\\Project\\Probes\\...`
  - Updated imports in commands, service provider bindings, and tests

### Configuration
New configuration options in `config/uplinkr.php` under `probes`:
```php
'execution_mode' => env('UPLINKR_PROBES_EXECUTION_MODE', 'direct'),
'queue_connection' => env('UPLINKR_PROBES_QUEUE_CONNECTION', 'sync'),
```

New storage option in `config/uplinkr.php` under `storage`:
```php
'pretty_print_probe_results' => (bool)env('UPLINKR_STORAGE_PRETTY_PRINT_PROBE_RESULTS', true),
```

Default remains `true` for backward compatibility.

### Fixed
- **Atomic Alert-State Persistence for Grouped Notifications**
  - `AlertDecisionHandler` now persists probe alert state immediately after each successfully delivered grouped notification batch
  - Prevents duplicate alerts when a later grouped notification fails in the same run
- **TLS Metadata in Aggregated Alert Messages**
  - Persisted `probe_tls_expiration_date` into `state.json` probe entries so alert decisions can reliably access TLS metadata
  - Aggregated mail output now renders `n/a` when TLS expiration is `null` instead of leaving the value empty
- **Config Command Output Escaping**
  - Escapes keys and values in `uplinkr:config` output to prevent accidental console markup parsing
  - Empty arrays are now displayed explicitly as `[]`
- **Analyze Command Test Stability**
  - Adjusted test setup for readonly `SummaryHandler`
  - Reduced brittle mocking and aligned test behavior with current handler design
- **Probe HTTP Test Stability**
  - Stabilized HTTP fakes for probe tests to avoid strict URL-match brittleness
- **Handler Consistency and Date Parsing**
  - `AnalyzeHandler::extractDateFromFilename()` now respects the configured probe filename separator
  - `PruneHandler` now parses probe-result dates according to configured grouping (`daily`, `hourly`, `monthly`)
  - Removed redundant JSON error check in legacy JSONL decode path
  - Normalized `Arr` imports in project handlers to `Illuminate\Support\Arr`
- **Storage and Object Consistency**
  - Standardized probe header persistence in project storage to use `headers`
  - Added backward-compatible support for legacy `header` key
  - `ProjectValues` now normalizes legacy probe entries from `header` to `headers`
  - Fixed minor documentation typo in `FileProbeResultsStorage` docblock

### Testing
- Extended `ProbeUrlHandlerTest` with job execution scenarios
- Added tests for direct execution mode
- Added tests for job dispatch mode
- Added tests for `executeProbe()` method
- Added coverage for:
  - TLS expiration field in probe results
  - Alert notification payload/log/mail propagation of TLS metadata
  - Configurable probe-result pretty printing
  - Config command escaping and explicit empty-array rendering
  - Custom filename separators in analyze date extraction
  - Hourly grouped probe-result pruning
  - Header key normalization in project values and project storage
  - Pretty-print probe-result config getter assertions in config object tests
  - Project-level aggregated alert notification dispatch behavior
  - Aggregated alert log output and deterministic probe ordering in notification payloads

### Migration Notes
- **No breaking changes** – default behavior remains synchronous (`direct` mode)
- Existing storage behavior remains unchanged by default (`pretty_print_probe_results = true`)
- To enable async execution:
  1. Set `UPLINKR_PROBES_EXECUTION_MODE=job` in `.env`
  2. Configure a queue connection: `UPLINKR_PROBES_QUEUE_CONNECTION=redis`
  3. Start queue workers: `php artisan queue:work redis`

### Benefits
- Non-blocking probe execution for high-frequency monitoring
- Better scalability with multiple probes across projects
- Improved response times for CLI commands and scheduler tasks
- Flexible deployment: choose sync or async based on your needs
- Better certificate observability through TLS expiration metadata
- Reduced storage footprint and I/O when compact probe-result JSON is enabled

---

## [0.2.0] – 2026-02-21

### Added
- **Asynchronous Probe Execution via Jobs**
  - New `ProbeUrl` job class for executing URL probes in queue workers
  - Configurable execution mode via `config/uplinkr.php`:
    - `direct`: Synchronous execution (default, backward compatible)
    - `job`: Asynchronous execution via Laravel queues
  - Queue connection configuration (`probes.queue_connection`)
  - Support for all Laravel queue drivers (sync, database, redis, sqs, beanstalkd)
- **New Command: `uplinkr:config`**
  - Display the current Uplinkr configuration in structured format
  - Similar to Laravel's `php artisan config:show uplinkr` command
  - Hierarchical display of all configuration values
- **TLS Metadata for Probe Results**
  - HTTPS probes now resolve certificate expiration dates
  - New result field: `probe_tls_expiration_date` (ISO-8601 or `null`)
  - Optional per-probe TLS options:
    - `tls.enabled`
    - `tls.timeout`
    - `tls.verify_peer`
    - `tls.verify_peer_name`
    - `tls.allow_self_signed`
    - `tls.peer_name`
    - `tls.cafile`
    - `tls.capath`

### Changed
- **Composer Platform Requirements**
  - Added `ext-openssl` as an explicit Composer requirement to ensure TLS certificate parsing support is available at install time
- **UrlHandler Refactoring**
  - `handle()` method now dispatches jobs when `execution_mode = 'job'`
  - Extracted probe execution logic into new public `executeProbe()` method
  - Returns `null` when dispatching jobs (instead of probe result array)
- **UplinkrConfig Extension**
  - Added `probeExecutionMode` property with getter and helper methods
  - Added `probeQueueConnection` property with getter
  - New convenience method: `shouldExecuteProbesAsJob()`
- **Improved Console Output**
  - `HandlesProbeOutput` trait now handles `null` results gracefully
  - New translation key: `probe_dispatched_as_job`
  - Clear feedback when probes are dispatched to queue vs executed directly
- **Alert Notification Payloads**
  - `probe_tls_expiration_date` is now propagated through alert decisions
  - Included in mail, webhook, and log notifications
- **Project-Level Alert Aggregation**
  - Alert notifications are now grouped per project instead of sending one message per failed probe
  - `AlertDecisionHandler` keeps the existing decision logic (`trigger_after_failures`, cooldown, enabled state) and sends grouped notifications after decision collection
  - Grouping key uses project + alert configuration to keep channel/cooldown semantics intact
- **Alert Notification Rendering**
  - `AlertNotificationHandler` now supports aggregated payloads via `probes[]` while remaining backward compatible with legacy single-probe payloads
  - Aggregated mail notifications now include a compact per-probe list (probe, failure count, TLS value)
  - Aggregated payload probes are sorted by probe name for deterministic output in mail, log, and webhook data
- **Probe Result Serialization**
  - Probe result file formatting is now configurable
  - Pretty printing can be disabled for compact JSON output in high-volume environments
- **Project Handler Structure**
  - Project handlers reorganized into focused namespaces:
    - `Handler\\Project\\Analyze\\...`
    - `Handler\\Project\\Archive\\...`
    - `Handler\\Project\\Probes\\...`
  - Updated imports in commands, service provider bindings, and tests

### Configuration
New configuration options in `config/uplinkr.php` under `probes`:
```php
'execution_mode' => env('UPLINKR_PROBES_EXECUTION_MODE', 'direct'),
'queue_connection' => env('UPLINKR_PROBES_QUEUE_CONNECTION', 'sync'),
```

New storage option in `config/uplinkr.php` under `storage`:
```php
'pretty_print_probe_results' => (bool)env('UPLINKR_STORAGE_PRETTY_PRINT_PROBE_RESULTS', true),
```

Default remains `true` for backward compatibility.

### Fixed
- **Atomic Alert-State Persistence for Grouped Notifications**
  - `AlertDecisionHandler` now persists probe alert state immediately after each successfully delivered grouped notification batch
  - Prevents duplicate alerts when a later grouped notification fails in the same run
- **TLS Metadata in Aggregated Alert Messages**
  - Persisted `probe_tls_expiration_date` into `state.json` probe entries so alert decisions can reliably access TLS metadata
  - Aggregated mail output now renders `n/a` when TLS expiration is `null` instead of leaving the value empty
- **Config Command Output Escaping**
  - Escapes keys and values in `uplinkr:config` output to prevent accidental console markup parsing
  - Empty arrays are now displayed explicitly as `[]`
- **Analyze Command Test Stability**
  - Adjusted test setup for readonly `SummaryHandler`
  - Reduced brittle mocking and aligned test behavior with current handler design
- **Probe HTTP Test Stability**
  - Stabilized HTTP fakes for probe tests to avoid strict URL-match brittleness
- **Handler Consistency and Date Parsing**
  - `AnalyzeHandler::extractDateFromFilename()` now respects the configured probe filename separator
  - `PruneHandler` now parses probe-result dates according to configured grouping (`daily`, `hourly`, `monthly`)
  - Removed redundant JSON error check in legacy JSONL decode path
  - Normalized `Arr` imports in project handlers to `Illuminate\Support\Arr`
- **Storage and Object Consistency**
  - Standardized probe header persistence in project storage to use `headers`
  - Added backward-compatible support for legacy `header` key
  - `ProjectValues` now normalizes legacy probe entries from `header` to `headers`
  - Fixed minor documentation typo in `FileProbeResultsStorage` docblock

### Testing
- Extended `ProbeUrlHandlerTest` with job execution scenarios
- Added tests for direct execution mode
- Added tests for job dispatch mode
- Added tests for `executeProbe()` method
- Added coverage for:
  - TLS expiration field in probe results
  - Alert notification payload/log/mail propagation of TLS metadata
  - Configurable probe-result pretty printing
  - Config command escaping and explicit empty-array rendering
  - Custom filename separators in analyze date extraction
  - Hourly grouped probe-result pruning
  - Header key normalization in project values and project storage
  - Pretty-print probe-result config getter assertions in config object tests
  - Project-level aggregated alert notification dispatch behavior
  - Aggregated alert log output and deterministic probe ordering in notification payloads

### Migration Notes
- **No breaking changes** – default behavior remains synchronous (`direct` mode)
- Existing storage behavior remains unchanged by default (`pretty_print_probe_results = true`)
- To enable async execution:
  1. Set `UPLINKR_PROBES_EXECUTION_MODE=job` in `.env`
  2. Configure a queue connection: `UPLINKR_PROBES_QUEUE_CONNECTION=redis`
  3. Start queue workers: `php artisan queue:work redis`

### Benefits
- Non-blocking probe execution for high-frequency monitoring
- Better scalability with multiple probes across projects
- Improved response times for CLI commands and scheduler tasks
- Flexible deployment: choose sync or async based on your needs
- Better certificate observability through TLS expiration metadata
- Reduced storage footprint and I/O when compact probe-result JSON is enabled

---

## [0.1.2] – 2026-02-08

### Fixed

- The user_agent configuration was defined in config/uplinkr.php but never used.
Now properly integrated into UplinkrConfig and UrlHandler, replacing the
hardcoded 'uplinkr-0.1.0' value with the configurable option.

---

## [0.1.1] – 2026-02-06

### Fixed
- Graceful handling of `League\Flysystem\UnableToListContents` exceptions
  when listing project directories in `storage/app/uplinkr`.

### Improved
- CLI commands no longer abort on filesystem permission issues
  in containerized environments.
- Improved observability via structured warning logs when storage
  listing fails.

### Logging
When directory listing fails, Uplinkr now emits a warning log with:
- `disk`
- `storage_path`
- `reason` (original exception message)

### Impact
- No breaking changes.
- Existing CLI workflows remain stable.
- Improved resilience in Docker / Podman setups with mounted volumes.

### Tests
- Added unit test covering the exception path and warning log emission.

---

## [0.1.0] – 2026-02-04

### Added
- CLI-first monitoring for URLs and APIs
- Project and probe management via Artisan commands
- File-based JSON storage for probe results (no database required)
- Multi-channel alerting (log, mail, webhook)
- Laravel scheduler integration for automated probe execution
- Prune commands for managing historical probe data
- Initial archive support for completed projects

### Design Principles
- No database dependency
- No external services
- Automation- and scheduler-friendly architecture

### Requirements
- PHP 8.2+
- Laravel 11.x

### Notes
- Initial MVP release
- API and internal behavior may evolve in future minor versions
- No breaking changes
