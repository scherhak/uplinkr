<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ManagerHandler;

/**
 * Class ProjectArchiveCommand
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:project:archive` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectArchiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:archive 
                            {--project= : Name of the project to archive}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archives a project or lists all available projects';

    public function handle(ManagerHandler $projectManagerHandler): int
    {
        $project = $this->option('project');
        $force = $this->option('force');

        $exists = $projectManagerHandler->exists(projectName: $project);

        if (!$force) {
            $this->info(__('uplinkr::messages.project_archive_start', [
                'project' => $project,
            ]));
        }

        if ($exists) {

            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.project_archive_start', [
                    'project' => $project,
                ]));
            }

            if ($execute) {
                $copied = $projectManagerHandler->archive(projectName: $project);

                if ($copied) {

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
}
