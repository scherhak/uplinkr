<?php

namespace Uplinkr\Commands;

use Uplinkr\Handler\ProbeUriHandler;
use Uplinkr\Helper\IdsHelper;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProbeUri extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe-by-uri {protocol} {uri} {--with-body} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Try to reach a url and store the response in the database';

    /**
     * Handles the execution of a command that processes a given URI.
     *
     * The method retrieves the URI argument and related options such as `force`
     * and `with-body`. If a URI is provided, it will either prompt for confirmation
     * or proceed directly based on the `force` option. If confirmed, the method
     * initiates the `ProbeUriHandler` for processing the URI. It returns a status
     * code indicating the success or failure of the operation.
     *
     * @return int Returns CommandAlias::SUCCESS if the URI is successfully processed
     *             or CommandAlias::INVALID if the process is canceled or the URI is invalid.
     * @throws \JsonException
     */
    public function handle(): int
    {
        $protocol = $this->argument('protocol');
        $uri = $this->argument('uri');
        $force = $this->option('force');
        $withBody = $this->option('with-body');

        // TODO Validate incoming data here
//        php artisan uplinkr:probe-by-uri https scherhak.com

        if (null !== $uri) {

            // if force isset - just let it through
            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(sprintf(
                    'Should %s request and loaded?',
                    $uri,
                ));
            }

            // execute it
            if ($execute) {
                $probeUriHandler = new ProbeUriHandler([
                    'protocol' => $protocol,
                    'uri' => $uri,
                    'withBody' => $withBody,
                ]);

                $probeUriHandler->execute();

                return CommandAlias::SUCCESS;
            }

            return CommandAlias::INVALID;
        }

        $this->error('No URI provided');

        return CommandAlias::INVALID;
    }
}
