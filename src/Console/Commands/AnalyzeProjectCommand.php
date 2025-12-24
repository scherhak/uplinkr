<?php

namespace Uplinkr\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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
    protected $signature = 'uplinkr:analyze  
                            {--project= : Name of the project to analyse}
                            {--from= : From date to analyse}
                            {--to= : To date to analyse}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyzes and evaluates the results of URL queries';

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

        $load = [];

        foreach ($files as $file) {
            $content = $analyzeHandler->readProbeResultFile(path: $file);
            $results = $analyzeHandler->decodeProbeResults(content: $content);
            $summary = $summaryHandler->summarizeProbeResults(decodedLines: $results);

            $load[\Arr::get($summary, '0')] = $summary;
            Log::debug('summary: ', $summary);
        }

        Log::debug('summary: ', [
            'load' => $load,
        ]);

        // In Laravel 12, console commands should return one of the built-in status codes
        // to indicate successful execution.
        return self::SUCCESS;
    }
}
