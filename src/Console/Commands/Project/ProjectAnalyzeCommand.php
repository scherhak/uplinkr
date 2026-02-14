<?php

namespace Uplinkr\Console\Commands\Project;

use Exception;
use Illuminate\Console\Command;
use JsonException;
use Uplinkr\Handler\Project\Analyze\AnalyzeHandler;
use Uplinkr\Handler\Project\Analyze\SummaryHandler;
use Uplinkr\Objects\Summary\ProbeResultsSummary;

/**
 * Class ProjectAnalyzeCommand
 * @package Uplinkr\Commands
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:project:analyze  
                            {--project= : Name of the project to analyse}
                            {--from= : From date to analyse}
                            {--to= : To date to analyse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyzes and evaluates the results of URL queries';

    /**
     * Execute the console command.
     *
     * @param AnalyzeHandler $analyzeHandler
     * @param SummaryHandler $summaryHandler
     * @return int
     * @throws Exception
     */
    public function handle(AnalyzeHandler $analyzeHandler, SummaryHandler $summaryHandler): int
    {
        $project = $this->option('project');
        $from = $this->option('from');
        $to = $this->option('to');

        $files = $analyzeHandler->probeResultsList($project, $from, $to);

        // If no project specified, $files is an associative array with project names as keys
        if ($project === null) {
            foreach ($files as $projectName => $projectFiles) {
                $this->analyzeProjectFiles($projectName, $projectFiles, $analyzeHandler, $summaryHandler);
            }
        } else {
            // Single project analysis
            $this->analyzeProjectFiles($project, $files, $analyzeHandler, $summaryHandler);
        }

        // In Laravel 12, console commands should return one of the built-in status codes
        // to indicate successful execution.
        return self::SUCCESS;
    }

    /**
     * Analyzes probe result files for a specific project.
     *
     * @param string $project The project name
     * @param array $files The array of file paths to analyze
     * @param AnalyzeHandler $analyzeHandler
     * @param SummaryHandler $summaryHandler
     * @return void
     * @throws JsonException
     */
    private function analyzeProjectFiles(
        string         $project,
        array          $files,
        AnalyzeHandler $analyzeHandler,
        SummaryHandler $summaryHandler
    ): void
    {
        $load = [];

        foreach ($files as $file) {
            $content = $analyzeHandler->readProbeResultFile(path: $file);
            $results = $analyzeHandler->decodeProbeResults(content: $content);
            $summary = $summaryHandler->summarizeProbeResults(decodedLines: $results);

            $date = $analyzeHandler->extractDateFromFilename($file);

            foreach ($summary as $urlSlug => $probeSummary) {
                if (!$probeSummary instanceof ProbeResultsSummary) {
                    continue;
                }

                if (!isset($load[$urlSlug])) {
                    $load[$urlSlug] = [];
                }

                $load[$urlSlug][$date] = $probeSummary->toArray();
            }
        }

        if (!empty($load)) {
            $analyzeHandler->saveAnalyzedResults($project, $load);
        }
    }
}
