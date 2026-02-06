<p align="center">
  <img src=".github/uplinkr-mascot-200.png" alt="Uplinkr Logo" width="200">
</p>

<h1 align="center">Uplinkr</h1>

<p align="center">
  CLI-first monitoring for Laravel — simple, local, reliable.<br>
  File-based monitoring for URLs & APIs. No external services.
</p>

<p align="center">
  <a href="https://github.com/scherhak/uplinkr/actions/workflows/tests.yml">
    <img src="https://github.com/scherhak/uplinkr/actions/workflows/tests.yml/badge.svg" alt="tests">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr">
    <img src="https://img.shields.io/packagist/v/scherhak/uplinkr" alt="Latest Stable Version">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr">
    <img src="https://img.shields.io/packagist/dt/scherhak/uplinkr" alt="Total Downloads">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr">
    <img src="https://img.shields.io/packagist/l/scherhak/uplinkr" alt="License">
  </a>
</p>

## What is Uplinkr?

**Uplinkr** is a lightweight, file-based uptime and response monitoring package for Laravel. It allows you to monitor your URLs and APIs without requiring a database, storing all probe results as JSON files. Perfect for developers who need simple, reliable monitoring integrated directly into their Laravel applications.

**Full documentation:** https://uplinkr.dev

---

## Quick Start

```bash
composer require scherhak/uplinkr
```

```bash
php artisan uplinkr:install
```

```bash
php artisan uplinkr:project:init --project=my-site
php artisan uplinkr:project:add:probe --project=my-site --url=https://example.com
php artisan uplinkr:project:run-probes
```

See full setup guide: https://uplinkr.dev/getting-started/quick-start/

---

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please see [SECURITY.md](SECURITY.md) for how to report them.

## License

MIT License. See [LICENSE.md](LICENSE.md).
