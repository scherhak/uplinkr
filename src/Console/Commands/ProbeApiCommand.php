<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProbeApiHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Traits\HandlesProbeOutput;

/**
 * Class ProbeApiCommand
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-api` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeApiCommand extends Command
{
    use HandlesProbeOutput;

    /**
     * The type of the probe, indicating the probe category or method.
     *
     * @var string
     */
    public const PROBE_TYPE = 'api';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe-api 
                            {--endpoint= : API endpoint URL} 
                            {--method=GET : HTTP method (GET, POST, PUT, DELETE, ...)} 
                            {--header=* : Additional headers, e.g. "Authorization: Bearer xxx"} 
                            {--body= : JSON body as string} 
                            {--project= : Optional project name}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an API probe with headers, method and optional body';

    public function handle(ProbeApiHandler $probeApiHandler, UplinkrConfig $config): int
    {
        $endpoint = $this->option('endpoint');
        $method = $this->option('method');
        $headers = $this->option('header');
        $body = $this->option('body');
        $project = $this->option('project');
        $force = $this->option('force');

        // url validating
        $validate = Validator::make(
            ['endpoint' => $endpoint],
            ['endpoint' => 'required|url']
        );

        if ($validate->passes()) {
            // if force isset - just let it through
            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.api_checking', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                ]));
            }

            if ($execute) {

                // finally, execute it
                $result = $probeApiHandler->with(data: [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'headers' => Arr::wrap($headers),
                    'body' => $body,
                    'project' => $project,
                ])->handle();

                if (!$force) {
                    $this->resultMessages(result: $result, project: $project, config: $config, probeType: self::PROBE_TYPE);
                }

                Log::debug('Uplinkr_ProbeApiCommand_debug', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body,
                    'project' => $project,
                    'force' => $force,
                ]);

                return CommandAlias::SUCCESS;
            }

            return CommandAlias::INVALID;
        }

        return CommandAlias::INVALID;
    }
}
