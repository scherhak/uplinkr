<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ArchiveHandler;
use Uplinkr\Support\CliIcon;

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

    /**
     * Execute the console command.
     *
     * @param ArchiveHandler $archiveHandler
     * @return int
     */
    public function handle(ArchiveHandler $archiveHandler): int
    {
        $project = $this->option('project');
        $force = $this->option('force');

        $exists = $archiveHandler->exists(projectName: $project);

        if ($exists) {

            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.project_archive_start', [
                    'project' => $project,
                ]));
            }

            if ($execute) {
                $copied = $archiveHandler->archive(projectName: $project);

                if ($copied) {

                    $this->info(CliIcon::OK->label(text: __('uplinkr::messages.project_archive_success', [
                        'project' => $project,
                    ])));

                    return CommandAlias::SUCCESS;
                }

                $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_archive_failed', [
                    'project' => $project,
                ])));
            }

            return CommandAlias::INVALID;
        }

        $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_not_found', [
            'project' => $project,
        ])));

        return CommandAlias::INVALID;
    }
}
