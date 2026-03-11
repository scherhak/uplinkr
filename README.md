<p align="center">
  <img src=".github/uplinkr-mascot-200.png" alt="Uplinkr Logo" width="200">
</p>

<h1 align="center">Uplinkr</h1>

<p align="center">
  CLI-first monitoring for Laravel — simple, local, reliable.<br>
  File-based monitoring for URLs & APIs. No external services.
</p>

<p align="center">
  <a href="https://github.com/scherhak/uplinkr/actions/workflows/tests.yml" style="text-decoration: none; border-bottom: none;">
    <img src="https://github.com/scherhak/uplinkr/actions/workflows/tests.yml/badge.svg" alt="tests">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr" style="text-decoration: none; border-bottom: none;">
    <img src="https://img.shields.io/packagist/v/scherhak/uplinkr" alt="Latest Stable Version">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr" style="text-decoration: none; border-bottom: none;">
    <img src="https://img.shields.io/packagist/dt/scherhak/uplinkr" alt="Total Downloads">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr" style="text-decoration: none; border-bottom: none;">
    <img src="https://img.shields.io/packagist/l/scherhak/uplinkr" alt="License">
  </a>
</p>

## What is Uplinkr?

**Uplinkr** is a lightweight, file-based uptime and response monitoring package for Laravel. It allows you to monitor your URLs and APIs without requiring a database, storing all probe results as JSON files. Perfect for developers who need simple, reliable monitoring integrated directly into their Laravel applications.

## Key Features

- **File-Based Storage** - No database required, all data stored as JSON files
- **Native Laravel Integration** - Built specifically for Laravel with Artisan commands
- **Automatic Scheduler Integration** - Run probes automatically with Laravel's task scheduler
- **Project Organization** - Group related probes together in projects
- **Global + Project Settings** - Separate global `uplinkr/settings.json` and per-project `settings.json`
- **Customizable Thresholds** - Define acceptable response times and failure tolerances
- **Multiple Notification Channels** - Log, email, and webhook support out of the box
- **I’m Alive Heartbeats** - Global heartbeat notifications with configurable interval and channels

## How It Works

Uplinkr follows a simple workflow:

1. **Create a Project** - Organize your monitoring targets into logical groups
2. **Add Probes** - Define URLs/APIs to monitor with optional custom headers, methods, and body data
3. **Configure Alerts** - Set up failure thresholds and notification channels
4. **Run Probes** - Execute manually or automatically via Laravel's scheduler
5. **Receive Alerts** - Get notified when probes fail or respond slowly
6. **Send Heartbeats** - Keep operators informed that monitoring is active via I’m alive

All probe results and settings are stored as JSON files, making it easy to inspect, back up, and track operational changes over time.

## Quick Start

This section walks you through the minimal setup required to start monitoring a URL or API.
No database, no external services — just install, configure, and run your first probe.


#### 1. Install Uplinkr via Composer

```bash
composer require scherhak/uplinkr
```

#### 2. Publish Configuration Files

```bash
php artisan uplinkr:install
```

#### 3. Create your first project

```bash
php artisan uplinkr:project:init --project=my-project
```

#### 4. Add the simplest check

```bash
php artisan uplinkr:project:add:probe --project=my-site --url=https://example.com
```

#### 5. Run the check for your first project

```bash
php artisan uplinkr:project:run-probes
```

## Deep Dive

Want to go beyond the basics?

- **Full documentation:** https://uplinkr.dev
  Complete reference, concepts, and architecture overview.

- **Getting started guide:** https://uplinkr.dev/getting-started/quick-start/  
  Step-by-step setup with explanations and best practices.

## Requirements

- **PHP:** 8.2 or higher
- **Laravel:** 11.x or 12.x
- **PHP extension:** `ext-openssl` (required for TLS certificate metadata)

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please see [SECURITY.md](SECURITY.md) for how to report them.

## License

MIT License. See [LICENSE.md](LICENSE.md).
