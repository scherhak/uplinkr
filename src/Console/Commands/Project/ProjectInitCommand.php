<?php

namespace Uplinkr\Console\Commands\Project;

use Carbon\Carbon;
use Illuminate\Console\Command;
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

        $initHandler->handle(options: [
            'project' => $project,
            'label' => $label,
            'description' => $description,
        ]);


        return CommandAlias::SUCCESS;
    }
}
