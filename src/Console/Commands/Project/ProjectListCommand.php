<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Handler\Project\ManagerHandler;

/**
 * Class ProjectListCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all available projects with the number of probes';

    public function handle(ListHandler $projectListHandler): int
    {
        $projects = $projectListHandler->listAll();

        foreach ($projects as $project) {
            $name = basename($project);
            $count = $projectListHandler->getProbesCount(path: $project);
            $this->info(sprintf('%s [%s]', $name, $count));
        }

        return CommandAlias::SUCCESS;
    }
}
