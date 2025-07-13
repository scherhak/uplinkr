# laravel-uplinkr

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
