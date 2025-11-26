<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProbeUrlHandler;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ProbeApi
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-api` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe-api 
                            {name? : Optional probe name from config(uplinkr.probes.*)} 
                            {--url= : API endpoint URL} 
                            {--method=GET : HTTP method (GET, POST, PUT, DELETE, ...)} 
                            {--header=* : Additional headers, e.g. "Authorization: Bearer xxx"} 
                            {--body= : JSON body as string} 
                            {--expect-status=200 : Expected HTTP status code}
                            {--project= : Optional project name}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an API probe with headers, method and optional body';

    public function handle(UplinkrConfig $config): int
    {
        $project = $this->option('project');
        $force = $this->option('force');

        // if force isset - just let it through
        if ($force) {
            $execute = true;
        } else {
            $execute = $this->confirm(__('uplinkr::messages.checking'));
        }

        if ($execute) {

            // execute it

            return CommandAlias::SUCCESS;
        }

        return CommandAlias::INVALID;
    }
}
