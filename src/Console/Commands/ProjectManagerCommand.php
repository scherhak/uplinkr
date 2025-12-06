<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProjectManagerHandler;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ProjectManagerCommand
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-api` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectManagerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project 
                            {--list : List all existing projects},
                            {--archive : Rename a project}
                            {--project= : Rename a project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function handle(ProjectManagerHandler $projectManagerHandler, UplinkrConfig $config): int
    {
        $list = $this->option('list');
        $archive = $this->option('archive');
        $project = $this->option('project');

        if($list) {
            $projects = $projectManagerHandler->listAll();

            foreach($projects as $project) {
                $name = basename($project);
                $count = $projectManagerHandler->getProbesCount(path: $project);
                $this->info(sprintf('%s [%s]', $name, $count));
            }

            return CommandAlias::SUCCESS;
        }

        if($archive && $project) {

            $exists = $projectManagerHandler->exists(projectName: $project);
            $this->info(sprintf('%s > %s', $project, $exists));

            if($exists) {
                $copied = $projectManagerHandler->archive(projectName: $project);

                $this->info(sprintf('%s > %s', $project, $copied));
                return CommandAlias::SUCCESS;
            }

        }

        return CommandAlias::INVALID;
    }

}
