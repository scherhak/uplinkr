<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ListHandler;

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

    /**
     * Execute the console command.
     *
     * @param ListHandler $listHandler
     * @return int
     */
    public function handle(ListHandler $listHandler): int
    {
        $projects = $listHandler->all();

        foreach ($projects as $project) {
            $name = basename($project);
            $count = $listHandler->countProbes(path: $project);
            $this->info(sprintf('%s [%s]', $name, $count));
        }

        return CommandAlias::SUCCESS;
    }
}
