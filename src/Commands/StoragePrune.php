<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Objects\Config\UplinkrConfig;
use Illuminate\Console\ConfirmableTrait;

/**
 * Class StoragePrune
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-by-uri` command.
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <uplinkr@scherhak.com>
 */
class StoragePrune extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:prune {project?} {--all} {--force}';

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
                $this->warn('Starting deletion process ...');
            }

            $this->info('Pruning storage ...');

            if($all) {
                Storage::disk('local')->deleteDirectory('uplinkr');
                $this->warn('All storage files deleted.');
                Storage::disk('local')->makeDirectory('uplinkr');
                $this->warn('Fresh storage created.');
            }

            return CommandAlias::SUCCESS;
        }

        return CommandAlias::INVALID;
    }
}
