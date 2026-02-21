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
