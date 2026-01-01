<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\RemoveProbeHandler;

/**
 * Class ProjectRemoveProbeCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectRemoveProbeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:remove:probe
                            {--url= : Target URL}
                            {--project= : Project name}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a probe command from the project';

    public function handle(RemoveProbeHandler $removeProbeHandler): int
    {
        $url = $this->option('url');
        $project = $this->option('project');
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
                $execute = $this->confirm(__('uplinkr::messages.project_remove_probe_start',
                    [
                        'url' => $url,
                        'project' => $project
                    ]
                ));
            }

            if ($execute) {
                $removeProbeHandler->handle(options: [
                    'url' => $url,
                    'project' => $project,
                ]);

                if (!$force) {
                    $this->info(__('uplinkr::messages.project_remove_probe_success'));
                }

                return CommandAlias::SUCCESS;
            }

            if (!$force) {
                $this->warn(__('uplinkr::messages.common_process_aborted'));
            }

            return CommandAlias::INVALID;
        }

        if (!$force) {
            $this->error(__('uplinkr::messages.project_remove_probe_failed'));
        }

        return CommandAlias::INVALID;
    }
}
