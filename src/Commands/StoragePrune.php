<?php

namespace Uplinkr\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\StoragePruneHandler;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class StoragePrune
 * @package Uplinkr\Commands
 *
 * @version 1
 * @copyright 2025-today Sascha Scherhak / uplinkr.dev
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class StoragePrune extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:prune {--project=} {--before=} {--wipe-all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Handles the pruning of files or directories based on the given configuration and options.
     *
     * @param UplinkrConfig $config The configuration instance containing various settings for pruning, such as paths and separators.
     * @return int Returns a status code indicating the outcome of the process:
     *             - CommandAlias::SUCCESS on successful execution.
     *             - CommandAlias::FAILURE if the process encounters an error.
     *             - CommandAlias::INVALID if no action is performed.
     */
    public function handle(UplinkrConfig $config,  StoragePruneHandler $handler): int
    {
        $project = $this->option('project');
        $before = $this->option('before');
        $all = $this->option('wipe-all');
        $force = $this->option('force');

        // if force isset - just let it through
        if ($force) {
            $execute = true;
        } else if ($all) {
            $execute = $this->confirmToProceed(__('uplinkr::messages.prune_all'));
        } else {
            $message = ($project && $before)
                ? "Are you sure you want to delete files in project '$project' before $before?"
                : __('uplinkr::messages.prune_project', ['project' => $project]);

            $execute = $this->confirm($message);
        }

        // execute it
        if ($execute) {
            if (!$force) {
                $this->warn('Starting pruning process ...');
            }

            // Projects section
            if ($project) {
                // Check if the project folder exists
                $projectPath = 'uplinkr/' . $project;

                // Specific logic for pruning by date
                if ($before) {
                    try {
                        $deletedCount = $handler->pruneByDate($project, $before);

                        if (!$force) {
                            if ($deletedCount > 0) {
                                $this->info("Deleted {$deletedCount} files older than {$before}.");
                            } else {
                                $this->warn("No files found older than {$before} or directory empty.");
                            }
                        }
                    } catch (\InvalidArgumentException $e) {
                        $this->error('Invalid date format for --before. Please use Y-m-d.');
                        return CommandAlias::FAILURE;
                    }
                }
                // Standard logic: Delete complete project folder if no --before is set
                else {
                    $projectFolderExists = Storage::disk('local')->exists($projectPath);

                    if ($projectFolderExists) {
                        Storage::disk('local')->deleteDirectory($projectPath);
                        if (!$force) {
                            $this->warn('All storage files deleted.');
                        }
                    } else {
                        if (!$force) {
                            $this->error('Project folder does not exist.');
                        }
                    }
                }
            } elseif ($all) {
                Storage::disk('local')->deleteDirectory('uplinkr');
                if (!$force) {
                    $this->warn('All storage files deleted.');
                }
                Storage::disk('local')->makeDirectory('uplinkr');
                if (!$force) {
                    $this->info('New folder for uplinkr created.');
                }
            } else {
                if (!$force) {
                    $this->warn('No storage files deleted.');
                }
            }

            return CommandAlias::SUCCESS;
        }

        return CommandAlias::INVALID;
    }
}
