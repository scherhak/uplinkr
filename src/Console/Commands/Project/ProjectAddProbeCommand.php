<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\AddProbeHandler;
use Uplinkr\Handler\Project\InitHandler;

/**
 * Class ProjectInitCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectAddProbeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:add
                            {--url= : Target URL}
                            {--method=GET : HTTP method (GET, POST, PUT, DELETE, ...)} 
                            {--header=* : Additional headers, e.g. "Authorization: Bearer xxx"} 
                            {--body= : JSON body as string} 
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new probe command to the project';

    public function handle(AddProbeHandler $addProbeHandler): int
    {
        $url = $this->option('url');
        $method = $this->option('method');
        $headers = $this->option('header');
        $body = $this->option('body');
        $force = $this->option('force');

        $addProbeHandler->handle(options: [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
        ]);

        return CommandAlias::SUCCESS;
    }
}
