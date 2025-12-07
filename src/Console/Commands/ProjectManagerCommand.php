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
                            {--project= : Name of the project to archive}
                            {--archive : Rename a project}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists projects and/or archives them';

    public function handle(ProjectManagerHandler $projectManagerHandler): int
    {
        $list = $this->option('list');
        $archive = $this->option('archive');
        $project = $this->option('project');
        $force = $this->option('force');

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

            if (!$force) {
                $this->info(__('uplinkr::messages.project_archive_start', [
                    'project' => $project,
                ]));
            }

            if($exists) {

                if ($force) {
                    $execute = true;
                } else {
                    $execute = $this->confirm(__('uplinkr::messages.project_archive_start', [
                        'project' => $project,
                    ]));
                }

                if ($execute) {
                    $copied = $projectManagerHandler->archive(projectName: $project);

                    if($copied) {

                        if (!$force) {
                            $this->info(__('uplinkr::messages.project_archive_success', [
                                'project' => $project,
                            ]));
                        }

                        return CommandAlias::SUCCESS;
                    }

                    if (!$force) {
                        $this->error(__('uplinkr::messages.project_archive_failed', [
                            'project' => $project,
                        ]));
                    }
                }

                return CommandAlias::INVALID;
            }

            if (!$force) {
                $this->error(__('uplinkr::messages.project_not_found', [
                    'project' => $project,
                ]));
            }

            return CommandAlias::INVALID;
        }

        if(!$archive && $project) {
            $this->warn(__('uplinkr::messages.project_archive_option_missing'));
        }

        return CommandAlias::INVALID;
    }
}
