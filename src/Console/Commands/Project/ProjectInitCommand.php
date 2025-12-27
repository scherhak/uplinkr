<?php

namespace Uplinkr\Console\Commands\Project;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\InitHandler;

/**
 * Class ProjectInitCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:init 
                            {--project= : Name of the project to initialize}
                            {--label= : Optional project name}
                            {--description= : Optional project description}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes a new project and creates the necessary JSON-Data and store it in the configured storage location.';

    public function handle(InitHandler $initHandler): int
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
                $execute = $this->confirm(__('uplinkr::messages.project_init_start',
                    [
                        'project' => $project
                    ]
                ));
            }

            if ($execute) {
                $initHandler->handle(options: [
                    'project' => $project,
                    'label' => $label,
                    'description' => $description,
                ]);

                if (!$force) {
                    $this->info(__('uplinkr::messages.project_init_success', [ 'project' => $project ]));
                }

                return CommandAlias::SUCCESS;
            }

            if (!$force) {
                $this->warn(__('uplinkr::messages.common_process_aborted'));
            }

            return CommandAlias::INVALID;

        }

        if (!$force) {
            $this->error(__('uplinkr::messages.project_init_failed'));
        }

        return CommandAlias::INVALID;
    }
}
