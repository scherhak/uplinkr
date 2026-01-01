<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\UpdateHandler;

/**
 * Class ProjectUpdateCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:update 
                            {--project= : Name of the project to update}
                            {--label= : Optional project name}
                            {--description= : Optional project description}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates an existing project and its JSON-Data.';

    /**
     * @throws JsonException
     */
    public function handle(UpdateHandler $updateHandler): int
    {
        $project = $this->option('project');
        $label = $this->option('label');
        $description = $this->option('description');
        $force = $this->option('force');

        $validate = Validator::make(
            [
                'project' => $project,
                'label' => $label,
                'description' => $description
            ],
            [
                'project' => 'required|string',
                'label' => 'nullable|string',
                'description' => 'nullable|string',
            ],
        );

        if ($validate->passes()) {

            // if force isset - just let it through
            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.project_update_start',
                    [
                        'project' => $project
                    ]
                ));
            }

            if ($execute) {
                $success = $updateHandler->handle(options: [
                    'project' => $project,
                    'label' => $label,
                    'description' => $description,
                ]);

                if ($success) {
                    if (!$force) {
                        $this->info(__('uplinkr::messages.project_update_success', ['project' => $project]));
                    }
                    return CommandAlias::SUCCESS;
                }

                $this->error(__('uplinkr::messages.project_update_failed', ['project' => $project]));
                return CommandAlias::FAILURE;
            }

            if (!$force) {
                $this->warn(__('uplinkr::messages.common_process_aborted'));
            }

            return CommandAlias::INVALID;

        }

        if (!$force) {
            $this->error(__('uplinkr::messages.project_update_failed_validation'));
        }

        return CommandAlias::INVALID;
    }
}
