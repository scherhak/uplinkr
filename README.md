# laravel-uplinkr [--WIP--]

# After package changes
composer update scherhak/laravel-uplinkr

# Config publishen
php artisan vendor:publish --tag=uplinkr-config
php artisan vendor:publish --tag=uplinkr-lang

# Artisan Command
php artisan uplinkr:check-urls

# Migrations migration (later)
php artisan migrate

# Caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear


uplinkr-laravel/
├── src/
│   ├── UplinkrServiceProvider.php
│   ├── Commands/          ← optional (deine Artisan-Commands)
│   ├── Notifications/     ← optional (wenn du Laravel Notifications nutzt)
│   └── weitere Klassen    ← z. B. Handler, Services
├── composer.json
├── config/
│   └── uplinkr.php
├── database/
│   └── migrations/       ← falls du Datenbanktabellen brauchst
├── routes/               ← optional (z. B. für Dashboard-Routen)
│   └── web.php
├── resources/
│   └── views/            ← optional (z. B. für Dashboard-Views)
├── README.md
├── LICENSE               ← wichtig, falls du es später veröffentlichen willst (z. B. MIT)
├── .gitignore


php artisan list uplinkr


resources/
└── lang/
├── en/
│   └── uplinkr.php
└── de/
└── uplinkr.php
