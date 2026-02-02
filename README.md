<div style="text-align: center;">
  <img src=".github/uplinkr-mascot-200.png" alt="Uplinkr Logo" width="200">

  <h1 style="margin-top: 0;font-size: 3rem;">uplinkr</h1>

  <p>
    Lightweight Laravel monitoring for URLs & APIs.<br>
    File-based. CLI-first. No external services.
  </p>
</div>

---

## What is Uplinkr?

**Uplinkr** is a lightweight, file-based uptime and response monitoring package for Laravel. It allows you to monitor your URLs and APIs without requiring a database, storing all probe results as JSON files. Perfect for developers who need simple, reliable monitoring integrated directly into their Laravel applications.

📖 **Full documentation:** https://uplinkr.dev

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

➡ See full setup guide: https://uplinkr.dev/getting-started/quick-start/

---

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please see [SECURITY.md](SECURITY.md) for how to report them.

## License

MIT License. See [LICENSE.md](LICENSE.md).
