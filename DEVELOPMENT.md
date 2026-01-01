# uplinkr [--WIP--]

* composer update scherhak/uplinkr -W
* composer dump-autoload -o

# Publish

* php artisan vendor:publish --tag=uplinkr-config
* php artisan vendor:publish --tag=uplinkr-lang

# Current commands

* php artisan uplinkr:probe:url --url=https://uplinkr.dev --project=upnkr-url-test
* php artisan uplinkr:probe:url --url=https://uplinkr.dev/api/health --method=GET --project=upkr-api-test
* php artisan uplinkr:project:list
* php artisan uplinkr:project:archive --project=scherhak-com
* php artisan uplinkr:prune {--project=} {--before=} {--wipe-all} {--force}
* php artisan uplinkr:analyze --project=scherhak-com
* php artisan uplinkr:analyze --project=scherhak-com --from=2025-12-09 --to=2025-12-10
* php artisan uplinkr:project:init --project=my-test-project --label="My Test Project" --description="This is a test project for uplinkr."
* php artisan uplinkr:project:update --project=my-test-project --label="My first UPLINKR Test Project" --description="This is a update for the test project for uplinkr 9000000."
* php artisan uplinkr:project:add:probe --url=https://uplinkr.dev --method=GET --project=my-test-project
* php artisan uplinkr:project:remove:probe --url=https://uplinkr.dev/foo --project=my-test-project
* php artisan uplinkr:project:run-probes
* php artisan uplinkr:project:run-selected-probe --project=my-test-project

# Wip commands



# Upcoming commands

Project lifecycle / metadata
* uplinkr:project:list — List all projects
* uplinkr:project:archive — Archive a project
* uplinkr:project:update — Update project metadata (e.g. display name, notes, status)

Project probe management (URLs, fully compatible with uplinkr:probe:url options)
* uplinkr:project:list:probe — List all probes (URLs) defined for a project
* uplinkr:project:update:probe — Update an existing probe definition (by id) within a project

(optional alternative naming: ...:delete)

Optional
* uplinkr:project:probe:enable — Enable a probe definition
* uplinkr:project:probe:disable — Disable a probe definition