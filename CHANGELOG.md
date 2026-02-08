# Changelog

All notable changes to this project will be documented in this file.

## [0.1.2] – 2026-02-xx

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

