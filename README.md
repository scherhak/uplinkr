# uplinkr [--WIP--]

* composer update scherhak/uplinkr -W
* composer dump-autoload -o

# Publish

* php artisan vendor:publish --tag=uplinkr-config
* php artisan vendor:publish --tag=uplinkr-lang

# Artisan Command

* php artisan uplinkr:probe-url --url=https://uplinkr.dev --project=upnkr-url-test
* php artisan uplinkr:probe-url --url=https://uplinkr.dev/api/health --method=GET --project=upkr-api-test
* php artisan uplinkr:project:list
* php artisan uplinkr:project:archive --project=scherhak-com
* php artisan uplinkr:prune {--project=} {--before=} {--wipe-all} {--force}
* php artisan uplinkr:analyze --project=scherhak-com
* php artisan uplinkr:analyze --project=scherhak-com --from=2025-12-09 --to=2025-12-10

# Upcoming commands

Project lifecycle / metadata
* uplinkr:project:init — Initialize a new project (creates the project container + metadata)
* uplinkr:project:update — Update project metadata (e.g. display name, notes, status)

Project probe management (URLs, fully compatible with uplinkr:probe-url options)
* uplinkr:project:probe:list — List all probes (URLs) defined for a project
* uplinkr:project:probe:add — Add a new URL probe definition to a project (url, method, header, body, force, enabled)
* uplinkr:project:probe:update — Update an existing probe definition (by id) within a project
* uplinkr:project:probe:remove — Remove a probe definition from a project (by id)
(optional alternative naming: ...:delete)

Optional
* uplinkr:project:probe:enable — Enable a probe definition
* uplinkr:project:probe:disable — Disable a probe definition