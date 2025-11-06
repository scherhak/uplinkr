<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProbeUriHandler;

/**
 * Class ProbeUri
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-by-uri` command.
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 */
class ProbeUri extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe-by-uri {project} {protocol} {uri} {--force}';

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
     * @example php artisan uplinkr:probe-by-uri https scherhak.com
     *
     * @return int Returns CommandAlias::SUCCESS if the URI is successfully processed
     *             or CommandAlias::INVALID if the process is canceled or the URI is invalid.
     */
    public function handle(ProbeUriHandler $probeUriHandler): int
    {
        $project = $this->argument('project');
        $protocol = $this->argument('protocol');
        $uri = $this->argument('uri');
        $force = $this->option('force');
        $withBody = $this->option('with-body');

        // if uri isset - let it through
        // TODO: validate complete uri
        if (null !== $uri) {

            // if force isset - just let it through
            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(sprintf(
                    __('uplinkr::messages.checking', ['uri' => $uri]),
                    $uri,
                ));
            }

            // execute it
            if ($execute) {

                // finally, execute it
                $probeUriHandler->with([
                    'project' => $project,
                    'protocol' => $protocol,
                    'uri' => $uri,
                ])->handle();

                return CommandAlias::SUCCESS;
            }

            return CommandAlias::INVALID;
        }

        $this->error('No URI provided');

        return CommandAlias::INVALID;
    }
}
