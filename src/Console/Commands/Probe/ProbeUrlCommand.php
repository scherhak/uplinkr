<?php

namespace Uplinkr\Console\Commands\Probe;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Traits\HandlesProbeOutput;

/**
 * Class ProbeUrlCommand
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-url` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeUrlCommand extends Command
{
    use HandlesProbeOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe:url 
                            {--url= : Target URL}
                            {--project= : Optional project name} 
                            {--method=GET : HTTP method (GET, POST, PUT, DELETE, ...)} 
                            {--header=* : Additional headers, e.g. "Authorization: Bearer xxx"} 
                            {--body= : JSON body as string} 
                            {--force : Force execution without confirmation}';

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
     * initiates the `UrlHandler` for processing the URI. It returns a status
     * code indicating the success or failure of the operation.
     *
     * @return int Returns CommandAlias::SUCCESS if the URI is successfully processed
     *             or CommandAlias::INVALID if the process is canceled or the URI is invalid.
     * @example php artisan uplinkr:probe-by-uri https scherhak.com
     *
     */
    public function handle(UrlHandler $probeUrlHandler, UplinkrConfig $config): int
    {
        $url = $this->option('url');
        $project = $this->option('project');
        $method = $this->option('method');
        $headers = $this->option('header');
        $body = $this->option('body');
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
                    __('uplinkr::messages.probe_checking', ['url' => $url]),
                    $url,
                ));
            }

            if ($execute) {

                // finally, execute it
                $result = $probeUrlHandler->with(data: [
                    'url' => $url,
                    'project' => $project,
                    'method' => $method,
                    'headers' => Arr::wrap($headers),
                    'body' => $body,
                ])->handle();

                $this->resultMessages(result: $result, project: $project, config: $config);

                return CommandAlias::SUCCESS;
            }

            return CommandAlias::INVALID;
        }

        $this->error(__('uplinkr::messages.probe_no_url_provided'));

        return CommandAlias::INVALID;
    }
}
