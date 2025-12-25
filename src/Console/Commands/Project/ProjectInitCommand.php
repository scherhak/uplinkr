<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\InitHandler;
use Uplinkr\Handler\Project\ListHandler;

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
                            {--name= : Optional project name}
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
        $name = $this->option('name');
        $description = $this->option('description');
        $force = $this->option('force');

        $initHandler->init();


        return CommandAlias::SUCCESS;
    }
}
