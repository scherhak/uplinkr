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
* php artisan uplinkr:prune {--project=} {--before=} {--wipe-all} {--force}
* php artisan uplinkr:project:analyze --project=scherhak-com
* php artisan uplinkr:project:analyze --project=scherhak-com --from=2025-12-09 --to=2025-12-10
* php artisan uplinkr:project:init --project=my-test-project --label="My Test Project" --description="This is a test
  project for uplinkr."
* php artisan uplinkr:project:update --project=my-test-project --label="My first UPLINKR Test Project" --description="
  This is a update for the test project for uplinkr 9000000."
* php artisan uplinkr:project:add:probe --url=https://uplinkr.dev --method=GET --project=my-test-project
* php artisan uplinkr:project:remove:probe --url=https://uplinkr.dev/foo --project=my-test-project
* php artisan uplinkr:project:archive --project=scherhak-com
* php artisan uplinkr:project:disable --project=my-test-project
* php artisan uplinkr:project:enable --project=my-test-project
* php artisan uplinkr:project:run-probes
* php artisan uplinkr:project:run-selected-probe --project=my-test-project
* php artisan uplinkr:project:alerts --project=scherhak-com
* php artisan uplinkr:project:alerts --project=scherhak-com --enabled=true --failures=5 --cooldown=10 --threshold=90
  --slow=10 --channels=mail
* php artisan uplinkr:project:alerts --project=scherhak-com --enabled=true --cooldown=30 --threshold=1000 --slow=10

# Wip commands

* php artisan uplinkr:project:alerts --project=scherhak-dev-test --enabled=true --failures=5 --cooldown=10 --threshold=90 --slow=10 --channels=log
* php artisan uplinkr:project:alerts --project=scherhak-dev-test --channels=log,mail
* php artisan uplinkr:project:alerts --project=scherhak-dev-test --cooldown=120
* 
* php artisan uplinkr:project:alerts --project=uplinkr-dev-test --enabled=true --failures=2 --cooldown=5 --channels=log
* php artisan uplinkr:project:alerts --project=uplinkr-dev-test --channels=log,mail
* php artisan uplinkr:project:alerts --project=uplinkr-dev-test --cooldown=60
* 
* php artisan uplinkr:project:alerts --project=scherhak-dev-test
* php artisan uplinkr:project:alert:decision --project=scherhak-dev-test
* php artisan uplinkr:project:alert:decision

### Alerts

* uplinkr:project:alert:add
* uplinkr:project:alert:list
* uplinkr:project:alert:remove
* uplinkr:project:alert:toggle (enable/disable)

# To adjust

* php artisan uplinkr:project:list – Throw a message if no project exists

# Upcoming commands

Project lifecycle / metadata
Project probe management (URLs, fully compatible with uplinkr:probe:url options)

* uplinkr:project:list:probe — List all probes (URLs) defined for a project
* uplinkr:project:update:probe — Update an existing probe definition (by id) within a project

(optional alternative naming: ...:delete)

Optional

* uplinkr:project:probe:enable — Enable a probe definition
* uplinkr:project:probe:disable — Disable a probe definition