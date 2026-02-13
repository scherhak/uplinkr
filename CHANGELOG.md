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

### Configuration
New configuration options in `config/uplinkr.php` under `probes`:
```php
'execution_mode' => env('UPLINKR_PROBES_EXECUTION_MODE', 'direct'),
'queue_connection' => env('UPLINKR_PROBES_QUEUE_CONNECTION', 'sync'),
```

### Testing
- Extended `ProbeUrlHandlerTest` with job execution scenarios
- Added tests for direct execution mode
- Added tests for job dispatch mode
- Added tests for `executeProbe()` method

### Migration Notes
- **No breaking changes** – default behavior remains synchronous (`direct` mode)
- To enable async execution:
  1. Set `UPLINKR_PROBES_EXECUTION_MODE=job` in `.env`
  2. Configure a queue connection: `UPLINKR_PROBES_QUEUE_CONNECTION=redis`
  3. Start queue workers: `php artisan queue:work redis`

### Benefits
- Non-blocking probe execution for high-frequency monitoring
- Better scalability with multiple probes across projects
- Improved response times for CLI commands and scheduler tasks
- Flexible deployment: choose sync or async based on your needs

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

