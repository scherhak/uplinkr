<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\ProbeApiHandler;
use Uplinkr\Handler\ProjectManagerHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Traits\HandlesProbeOutput;

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
                            {--rename= : Rename a project}
                            {--to= : New name when renaming a project}
                            {--list : List all existing projects}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function handle(ProjectManagerHandler $projectManagerHandler, UplinkrConfig $config): int
    {
        $rename = $this->option('rename');
        $to = $this->option('to');
        $list = $this->option('list');


        return CommandAlias::INVALID;
    }
}
