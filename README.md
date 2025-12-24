# uplinkr [--WIP--]

composer update scherhak/uplinkr -W
composer dump-autoload -o

# Config publishen
php artisan vendor:publish --tag=uplinkr-config
php artisan vendor:publish --tag=uplinkr-lang

# Artisan Command
php artisan uplinkr:probe-url --url=https://uplinkr.dev --project=upnkr-url-test
php artisan uplinkr:probe-url --url=https://uplinkr.dev/api/health --method=GET --project=upkr-api-test
php artisan uplinkr:project --list
php artisan uplinkr:project --project=scherhak-com --archive
php artisan uplinkr:prune {--project=} {--before=} {--wipe-all} {--force}
php artisan uplinkr:analyze --project=scherhak-com
php artisan uplinkr:analyze --project=scherhak-com --from=2025-12-09 --to=2025-12-10