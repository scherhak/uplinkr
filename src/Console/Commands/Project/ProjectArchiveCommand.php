<?php

namespace Uplinkr\Console\Commands\Project;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\Archive\ArchiveHandler;
use Uplinkr\Support\CliIcon;
use Uplinkr\Support\Sanitizer;

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
    public function handle(ArchiveHandler $archiveHandler, Sanitizer $sanitizer): int
    {
        $projectInput = $this->option('project');
        $force = $this->option('force');

        if ($projectInput === null || trim((string)$projectInput) === '') {
            $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_archive_option_missing')));

            return CommandAlias::INVALID;
        }

        $project = $sanitizer->project($projectInput);
        if ($project === '') {
            $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_archive_option_missing')));

            return CommandAlias::INVALID;
        }

        $exists = $archiveHandler->exists(projectName: $project);

        if ($exists) {

            if ($force) {
                $execute = true;
            } else {
                $execute = $this->confirm(__('uplinkr::messages.project_archive_start', [
                    'project' => $projectInput,
                ]));
            }

            if ($execute) {
                $copied = $archiveHandler->archive(projectName: $project);

                if ($copied) {

                    $this->info(CliIcon::OK->label(text: __('uplinkr::messages.project_archive_success', [
                        'project' => $projectInput,
                    ])));

                    return CommandAlias::SUCCESS;
                }

                $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_archive_failed', [
                    'project' => $projectInput,
                ])));
            }

            return CommandAlias::INVALID;
        }

        $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.project_not_found', [
            'project' => $projectInput,
        ])));

        return CommandAlias::INVALID;
    }
}
