<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ProbeResultsPrune
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-by-uri` command.
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <uplinkr@scherhak.com>
 */
class ProbeResultsPrune extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:probe-results-prune {project?} {--all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function handle(UplinkrConfig $config): int
    {
        $project = $this->argument('project');
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
                $projectFolderExists = Storage::disk('local')->exists('uplinkr/probes' . '/' . $project);

                if ($projectFolderExists) {
                    Storage::disk('local')->deleteDirectory('uplinkr/probes' . '/' . $project);
                    $this->warn('All storage files deleted.');
                } else {
                    $this->error('Project folder does not exist.');
                }

            } elseif ($all) {
                Storage::disk('local')->deleteDirectory('uplinkr/probes');
                $this->warn('All storage files deleted.');
                Storage::disk('local')->makeDirectory('uplinkr/probes');
                $this->warn('Fresh storage created.');
            } else {
                $this->warn('No storage files deleted.');
            }

            return CommandAlias::SUCCESS;
        }

        return CommandAlias::INVALID;
    }
}
