<?php

namespace Uplinkr\Commands\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProbeUrlHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Traits\HandlesProbeOutput;

/**
 * Class ProbeUrl
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-url` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeUrl extends Command
{
    use HandlesProbeOutput;

    /**
     * The type of the probe, indicating the probe category or method.
     *
     * @var string
     */
    public const PROBE_TYPE = 'url';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe-url 
                            {--url= : Target URL}
                            {--project= : Optional project name} 
                            {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Try to reach a url and store the response within basic file system';

    /**
     * Handles the execution of a command that processes a given URI.
     *
     * The method retrieves the URI argument and related options such as `force`
     * and `with-body`. If a URI is provided, it will either prompt for confirmation
     * or proceed directly based on the `force` option. If confirmed, the method
     * initiates the `ProbeUrlHandler` for processing the URI. It returns a status
     * code indicating the success or failure of the operation.
     *
     * @return int Returns CommandAlias::SUCCESS if the URI is successfully processed
     *             or CommandAlias::INVALID if the process is canceled or the URI is invalid.
     * @example php artisan uplinkr:probe-by-uri https scherhak.com
     *
     */
    public function handle(ProbeUrlHandler $probeUrlHandler, UplinkrConfig $config): int
    {
        $url = $this->option('url');
        $project = $this->option('project');
        $force = $this->option('force');

        // url validating
        $validate = Validator::make(
            ['url' => $url],
            ['url' => 'required|url']
        );

        // if uri isset - let it through
        if ($validate->passes()) {

            // if force isset - just let it through
            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(sprintf(
                    __('uplinkr::messages.url_checking', ['url' => $url]),
                    $url,
                ));
            }

            if ($execute) {

                // finally, execute it
                $result = $probeUrlHandler->with(data: [
                    'url' => $url,
                    'project' => $project,
                ])->handle();

                if (!$force) {
                    $this->resultMessages(result: $result, project: $project, config: $config, probeType: self::PROBE_TYPE);
                }

                return CommandAlias::SUCCESS;
            }

            return CommandAlias::INVALID;
        }

        $this->error(__('uplinkr::messages.no_url_provided'));

        return CommandAlias::INVALID;
    }
}
