<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
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
    protected $signature = 'uplinkr:project:add:probe
                            {--url= : Target URL}
                            {--project= : Project name}
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
        $project = $this->option('project');
        $method = $this->option('method');
        $headers = $this->option('header');
        $body = $this->option('body');
        $force = $this->option('force');

        // url validating
        $validate = Validator::make(
            [
                'url' => $url,
                'project' => $project
            ],
            [
                'url' => 'required|url',
                'project' => 'required|string'
            ],
        );

        if ($validate->passes()) {

            // if force isset - just let it through
            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.project_add_probe_start',
                    [
                        'url' => $url,
                        'project' => $project
                    ]
                ));
            }

            if ($execute) {
                $addProbeHandler->handle(options: [
                    'url' => $url,
                    'project' => $project,
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body,
                ]);

                if (!$force) {
                    $this->info(__('uplinkr::messages.project_add_probe_success'));
                }

                return CommandAlias::SUCCESS;
            }

            if (!$force) {
                $this->warn(__('uplinkr::messages.common_process_aborted'));
            }

            return CommandAlias::INVALID;
        }

        if (!$force) {
            $this->error(__('uplinkr::messages.project_add_probe_failed'));
        }

        return CommandAlias::INVALID;
    }
}
