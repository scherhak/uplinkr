<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
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
    protected $signature = 'uplinkr:prune {--project=} {--before=} {--all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function handle(UplinkrConfig $config): int
    {
        $project = $this->option('project');
        $before = $this->option('before');
        $all = $this->option('all');
        $force = $this->option('force');

        // if force isset - just let it through
        if ($force) {
            $execute = true;
        } else if ($all) {
            $execute = $this->confirmToProceed(__('uplinkr::messages.prune_all'));
        } else {
            $execute = $this->confirm(__('uplinkr::messages.prune_project',
                [
                    'project' => $project
                ]
            ));
        }

        // execute it
        if ($execute) {
            if (!$force) {
                $this->warn('Starting pruning process ...');
            }

            // Projects section
            if ($project) {
                // Check if the project folder exists
                $projectFolderExists = Storage::disk('local')->exists('uplinkr/' . $project);

                if ($projectFolderExists) {
                    Storage::disk('local')->deleteDirectory('uplinkr/' . $project);
                    if (!$force) {
                        $this->warn('All storage files deleted.');
                    }
                } else {
                    if (!$force) {
                        $this->error('Project folder does not exist.');
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
