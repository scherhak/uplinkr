<?php

namespace Uplinkr\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProbeUrlHandler;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ClearStorage
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-by-uri` command.
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <uplinkr@scherhak.com>
 */
class ClearStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:clear {project?} {--all} {--force}';

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
        } else {
            if($all) {
                $execute = $this->confirm(__('uplinkr::messages.clear_all'));
            } else {
                $execute = $this->confirm(__('uplinkr::messages.clear_project', ['project' => $project]));
            }
        }

        // execute it
        if ($execute) {
            if (!$force) {
                // TODO Explanatory text regarding the deletion process
            }

            return CommandAlias::SUCCESS;
        }

        return CommandAlias::INVALID;
    }
}
