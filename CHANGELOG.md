# Changelog

All notable changes to this project will be documented in this file.

## [0.2.0] – 2026-02-xx

### Added
- **Asynchronous Probe Execution via Jobs**
  - New `ProbeUrl` job class for executing URL probes in queue workers
  - Configurable execution mode via `config/uplinkr.php`:
    - `direct`: Synchronous execution (default, backward compatible)
    - `job`: Asynchronous execution via Laravel queues
  - Queue connection configuration (`probes.queue_connection`)
  - Support for all Laravel queue drivers (sync, database, redis, sqs, beanstalkd)
- **New Command: `uplinkr:config`**
  - Display current Uplinkr configuration in structured format
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
