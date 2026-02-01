<p align="center">
  <img src=".github/uplinkr-logo.png" alt="Uplinkr Logo" width="160">
</p>

<h1 align="center">Uplinkr</h1>

<p align="center">
  Lightweight Laravel monitoring for URLs & APIs.<br>
  File-based. CLI-first. No external services.
</p>

<p align="center">
  <a href="https://packagist.org/packages/scherhak/uplinkr">
    <img src="https://img.shields.io/packagist/v/scherhak/uplinkr.svg?style=flat-square">
  </a>
  <a href="https://packagist.org/packages/scherhak/uplinkr">
    <img src="https://img.shields.io/packagist/dt/scherhak/uplinkr.svg?style=flat-square">
  </a>
  <a href="LICENSE.md">
    <img src="https://img.shields.io/packagist/l/scherhak/uplinkr.svg?style=flat-square">
  </a>
</p>

---

## What is Uplinkr?

**Uplinkr** is a native Laravel package for monitoring reachability and response
times of URLs and APIs — without databases, SaaS dashboards, or external services.

Run probes via Artisan, store results as JSON, evaluate alerts, and keep full
control over your monitoring stack.

---

## Why Uplinkr?

- ✅ Native **Laravel & Artisan** integration
- ✅ **File-based JSON storage** (no database)
- ✅ Built-in **alerting** (mail, webhook, log)
- ✅ CLI-first & scheduler-friendly
- ✅ Self-hosted, transparent, extensible

👉 Read more: https://uplinkr.dev/docs/why-uplinkr

---

## Install & Run

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

---

## Documentation

Full documentation, command reference, alerts, and configuration:

➡ https://uplinkr.dev/docs

---

## License

MIT License. See [LICENSE.md](LICENSE.md).
