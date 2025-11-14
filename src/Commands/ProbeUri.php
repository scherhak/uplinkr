<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'uplinkr:probe-by-uri {protocol} {uri} {project?} {--force}';

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
     * @example php artisan uplinkr:probe-by-uri https scherhak.com
     *
     */
    public function handle(ProbeUriHandler $probeUriHandler): int
    {
        $protocol = $this->argument('protocol');
        $uri = $this->argument('uri');
        $project = $this->argument('project');
        $force = $this->option('force');

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
                $probeUriHandler->with(data: [
                    'protocol' => $protocol,
                    'uri' => $uri,
                    'project' => $project,
                ])->handle();

                if (!$force) {
                    $this->info(__('uplinkr::messages.stored', ['project' => $project,]));
                }

                return CommandAlias::SUCCESS;
            }

            return CommandAlias::INVALID;
        }

        $this->error('No URI provided');

        return CommandAlias::INVALID;
    }
}
