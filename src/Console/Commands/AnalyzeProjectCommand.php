<?php

namespace Uplinkr\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Uplinkr\Handler\Project\AnalyzeHandler;
use Uplinkr\Handler\Project\SummaryHandler;

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
     * @throws Exception
     */
    public function handle(AnalyzeHandler $analyzeHandler, SummaryHandler $summaryHandler): int
    {
        $project = $this->option('project');
        $from = $this->option('from');
        $to = $this->option('to');
        $force = $this->option('force');

        $files = $analyzeHandler->probeResultsList($project, $from, $to);

//        Log::debug('Analyse result: ', [
//            'files' => $files,
//        ]);

        foreach ($files as $file) {
            $lines = $analyzeHandler->readProbeResultFile($file);
            $results = $analyzeHandler->decodeProbeResultLines($lines);
//            $summary = $summaryHandler->summarizeProbeResults($results);
            $summary = $summaryHandler->summarizeProbeResults($results);

            Log::debug('summary: ', [
                'summary' => $summary,
            ]);

//            foreach ($lines as $line) {
//                Log::debug('Analyse result: ', [
//                    'result' => json_decode($line, true),
//                ]);
//            }
        }

        // In Laravel 12, console commands should return one of the built-in status codes
        // to indicate successful execution.
        return self::SUCCESS;
    }
}
