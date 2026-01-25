<?php

namespace Uplinkr\Console\Commands\Prune;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Storage\PruneHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\CliIcon;

/**
 * Class PruneStorageCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class PruneStorageCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:prune 
                            {--project= : Optional project name to prune} 
                            {--before= : Before date to prune files, e.g. 2021-01-01} 
                            {--wipe-all : Wipe all files from storage} 
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combines various options for deleting existing data and files related to projects and results.';

    /**
     * Handles the pruning of files or directories based on the given configuration and options.
     *
     * @param UplinkrConfig $config The configuration instance containing various settings for pruning, such as paths and separators.
     * @return int Returns a status code indicating the outcome of the process:
     *             - CommandAlias::SUCCESS on successful execution.
     *             - CommandAlias::FAILURE if the process encounters an error.
     *             - CommandAlias::INVALID if no action is performed.
     */
    public function handle(UplinkrConfig $config, PruneHandler $storagePruneHandler): int
    {
        $project = $this->option('project');
        $before = $this->option('before');
        $wipeAll = $this->option('wipe-all');
        $force = $this->option('force');

        // if force isset - just let it through
        if ($force) {
            $execute = true;
        } else if ($wipeAll) {
            $execute = $this->confirmToProceed(__('uplinkr::messages.prune_wipe_all'));
        } else {
            $message = ($project && $before)
                ? __('uplinkr::messages.prune_before', ['project' => $project, 'before' => $before])
                : __('uplinkr::messages.prune_project', ['project' => $project]);

            $execute = $this->confirm($message);
        }

        // execute it
        if ($execute) {
            $this->warn(CliIcon::WARN->label(__('uplinkr::messages.prune_start')));

            // Projects section
            if ($project) {
                // Check if the project folder exists
                $projectPath = sprintf('%s/%s', $config->getStoragePath(), $project);

                // Specific logic for pruning by date
                if ($before) {
                    try {
                        // prune files by date
                        $deletedCount = $storagePruneHandler->pruneBeforeDate($project, $before);

                        if ($deletedCount > 0) {
                            $this->line(__('uplinkr::messages.prune_before_count_deleted_files', [
                                'deletedCount' => $deletedCount,
                                'before' => $before
                            ]));
                        } else {
                            $this->warn(CliIcon::WARN->label(__('uplinkr::messages.prune_before_no_files_found', [
                                'before' => $before
                            ])));
                        }
                    } catch (InvalidArgumentException $e) {
                        $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.prune_before_invalid_date_format')));

                        return CommandAlias::FAILURE;
                    }
                } else {
                    // Standard logic: Delete the complete project folder if no --before is set
                    $projectFolderExists = Storage::disk($config->getStorageDisc())->exists($projectPath);

                    if ($projectFolderExists) {
                        Storage::disk($config->getStorageDisc())->deleteDirectory($projectPath);
                        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.prune_project_by_name_success', [
                            'project' => $project]
                        )));
                    } else {
                        $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.prune_project_folder_does_not_exists', [
                            'project' => $project
                        ])));
                    }
                }
            } elseif ($wipeAll) {

                if ($config->allowCompleteWipe()) {
                    $storagePruneHandler->deleteDirectory($config->getStoragePath());
                    $this->warn(CliIcon::WARN->label(__('uplinkr::messages.prune_wipe_all_success')));
                    $storagePruneHandler->makeDirectory($config->getStoragePath());
                    $this->info(CliIcon::OK->label(__('uplinkr::messages.prune_wipe_all_new_folder_created')));
                } else {
                    $this->error(CliIcon::ERROR->label(__('uplinkr::messages.prune_wipe_all_not_allowed')));
                }
            } else {
                $this->warn(CliIcon::WARN->label(__('uplinkr::messages.prune_wipe_all_no_files_wiped')));
            }

            return CommandAlias::SUCCESS;
        }

        return CommandAlias::INVALID;
    }
}
