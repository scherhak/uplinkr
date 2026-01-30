<p align="center">
    <img src=".github/uplinkr-logo.png" alt="Uplinkr Logo" width="200">
</p>

# Uplinkr

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scherhak/uplinkr.svg?style=flat-square)](https://packagist.org/packages/scherhak/uplinkr)
[![Total Downloads]( https://img.shields.io/packagist/dt/scherhak/uplinkr.svg?style=flat-square)](https://packagist.org/packages/scherhak/uplinkr)
[![License](https://img.shields.io/packagist/l/scherhak/uplinkr.svg?style=flat-square)](https://packagist.org/packages/scherhak/uplinkr)

Uplinkr is a lightweight, file-based Laravel package for monitoring website availability with zero external
dependencies. It allows you to define projects and probes (URLs) to monitor their uptime and response times, storing
results directly in your local filesystem.

For more information, visit the official homepage: [uplinkr.dev](https://uplinkr.dev)

## Features

- **Project-based organization**: Group your probes into logical projects.
- **File-based storage**: No database required. All configurations and results are stored as JSON files.
- **Detailed analysis**: Analyze response times, status codes, and reachability.
- **Alerting**: Integrated alert system with support for multiple channels (e.g., Mail, Webhook, Log) and customizable thresholds.
- **Scheduler integration**: Automatically run probes using Laravel's task scheduler.
- **Flexible commands**: Manage projects and probes easily via Artisan commands.
- **Customizable**: Configure storage paths, disks, and more.

## Installation

You can install the package via composer:

```bash
composer require scherhak/uplinkr
```

The service provider will automatically register itself. To install the package and publish the required assets, run:

```bash
php artisan uplinkr:install
```

You can also enable the automatic scheduler integration during installation:

```bash
php artisan uplinkr:install --scheduler
```

Alternatively, you can publish the configuration file manually with:

```bash
php artisan vendor:publish --provider="Uplinkr\UplinkrServiceProvider" --tag="uplinkr-config"
```

## Usage

Uplinkr provides several Artisan commands to manage your monitoring.

### Project Management

#### Initialize a New Project

Create a new project to start monitoring URLs. If the project already exists, you will be asked for confirmation to
re-initialize it (metadata will be overwritten, but `created_at` and `probes` will be preserved).

```bash
php artisan uplinkr:project:init --project=my-website --label="My Website" --description="Main company website monitoring"
```

#### Update Project Metadata or Status

Update the label, description, or status of an existing project without affecting probes or creation date.

```bash
php artisan uplinkr:project:update --project=my-website --label="Updated Label" --description="Updated description"
```

#### List Projects

List all projects and the number of stored probe result files.

```bash
php artisan uplinkr:project:list
```

#### Archive a Project

Copy a project and its results to the archive folder (the original remains).

```bash
php artisan uplinkr:project:archive --project=my-website
```

#### Enable or Disable a Project

```bash
php artisan uplinkr:project:update --project=my-website --status=disabled
php artisan uplinkr:project:update --project=my-website --status=enabled
```

### Probe Management

#### Add a Probe to a Project

Add a URL to be monitored under a specific project. You can also specify HTTP method, custom headers, and a request body.

```bash
php artisan uplinkr:project:add:probe --project=my-website --url=https://example.com --method=POST --header="Authorization: Bearer token" --body='{"key":"value"}' --latency=2000
```

#### Remove a Probe from a Project

Remove a URL from a specific project.

```bash
php artisan uplinkr:project:remove:probe --project=my-website --url=https://example.com
```

### Executing Probes

All execution commands support a `--force` flag to skip confirmation prompts.

#### Run All Probes for All Projects

Execute every defined probe across all projects (disabled projects are skipped).

```bash
php artisan uplinkr:project:run-probes
```

#### Run All Probes for a Specific Project

Execute all defined probes for a single selected project.

```bash
php artisan uplinkr:project:run-selected --project=my-website
```

#### Manual URL Probing

Probe a specific URL directly and optionally assign it to a project.

```bash
php artisan uplinkr:probe:url --url=https://example.com --project=my-website --latency=1500
```

### Alert Management

#### Configure Alerts for a Project

Define when and how alerts should be triggered for a specific project.

```bash
php artisan uplinkr:project:alerts --project=my-website --enabled=true --failures=3 --cooldown=30 --threshold=2000 --slow=5 --channels=mail,webhook
```

#### Check Alert Decisions

Check if any alerts should be triggered based on the latest probe results. You can filter by project or check all projects.

```bash
# Check all projects
php artisan uplinkr:project:alert:decision

# Check a specific project
php artisan uplinkr:project:alert:decision --project=my-website
```

### Analysis and Maintenance

#### Analyze Results

Generate a summary of probe results for a project, including average response times and status code distribution. You can optionally filter by a date range.

```bash
# Analyze a specific project
php artisan uplinkr:project:analyze --project=my-website

# Analyze a project with a date range
php artisan uplinkr:project:analyze --project=my-website --from=2025-01-01 --to=2025-01-31

# Analyze all projects
php artisan uplinkr:project:analyze
```

#### Prune Storage

Clean up old probe results or wipe all data.

```bash
# Delete results for a project before a specific date
php artisan uplinkr:prune --project=my-website --before=2023-01-01

# Wipe all Uplinkr data (requires 'allow_complete_wipe' to be true in config)
php artisan uplinkr:prune --wipe-all
```

## Configuration

The configuration file is located at `config/uplinkr.php`. Common options include:

- `storage.disk`: The Laravel filesystem disk to use (default: `local`).
- `storage.path`: The base path for storing Uplinkr data (default: `uplinkr`).
- `storage.allow_complete_wipe`: Safety setting to allow deletion of all data (default: `false`).
- `probes.standard_latency`: The default maximum execution time for a URL probe in ms (default: `1500`).
- `projects.standard_project`: Default project name used when none is specified.
- `notifications.channels.*`: Configure alert notifications (log, mail, webhook).
- `scheduler.enabled`: Enable the automatic execution of probes via Laravel's task scheduler (default: `false`).
- `scheduler.cron`: The cron expression for the scheduler (default: `* * * * *`).

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Security

Please see [SECURITY.md](SECURITY.md) for our security policy.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for release notes.
