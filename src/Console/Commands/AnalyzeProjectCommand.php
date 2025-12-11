<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\AnalyzeHandler;

/**
 * Class ProjectManagerCommand
 * @package Uplinkr\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:probe-api` command.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AnalyzeProjectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:analyse  
                            {--project= : Name of the project to analyse}
                            {--from= : From date to analyse}
                            {--to= : To date to analyse}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists projects and/or archives them';

    /**
     * @throws \Exception
     */
    public function handle(AnalyzeHandler $analyzeHandler): int
    {
        $project = $this->option('project');
        $from = $this->option('from');
        $to = $this->option('to');
        $force = $this->option('force');

        $files   = $analyzeHandler->probeResultsList($project, $from, $to);

//        foreach ($files as $file) {
//            echo $file . "\n";
//        }


        Log::debug('Analyse result: ', [
            'files' => $files,
        ]);


        return CommandAlias::INVALID;
    }
}
