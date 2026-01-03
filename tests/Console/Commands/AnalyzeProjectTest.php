<?php

namespace Console\Commands;

use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\AnalyzeHandler;
use Uplinkr\Handler\Project\SummaryHandler;
use Uplinkr\Tests\TestCase;
use Symfony\Component\Console\Command\Command as CommandAlias;

class AnalyzeProjectTest extends TestCase
{
    private MockInterface|AnalyzeHandler $analyzeHandlerMock;
    private MockInterface|SummaryHandler $summaryHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzeHandlerMock = Mockery::mock(AnalyzeHandler::class);
        $this->summaryHandlerMock = Mockery::mock(SummaryHandler::class);

        $this->app->instance(AnalyzeHandler::class, $this->analyzeHandlerMock);
        $this->app->instance(SummaryHandler::class, $this->summaryHandlerMock);
    }

    /**
     * Tests that the command executes successfully when results are found.
     */
    public function test_analyze_project_success(): void
    {
        $project = 'test-project';
        $files = ['path/to/test@2023-01-01.json'];
        $content = '{"some":"json"}';
        $results = [['url' => 'http://example.com']];
        
        $summaryObj = new \Uplinkr\Objects\Summary\ProbeResultsSummary(
            url: 'http://example.com',
            total: 1,
            reachable: 1,
            unreachable: 0,
            error: 0,
            unknown: 0,
            firstExecutedAt: '100',
            lastExecutedAt: '100',
            avgDurationMs: 100.0,
            statusHeaderCounts: [200 => 1]
        );
        
        $summary = ['example_com' => $summaryObj];

        $this->analyzeHandlerMock->shouldReceive('probeResultsList')
            ->with($project, null, null)
            ->once()
            ->andReturn($files);

        $this->analyzeHandlerMock->shouldReceive('readProbeResultFile')
            ->with('path/to/test@2023-01-01.json')
            ->once()
            ->andReturn($content);

        $this->analyzeHandlerMock->shouldReceive('decodeProbeResults')
            ->with($content)
            ->once()
            ->andReturn($results);

        $this->summaryHandlerMock->shouldReceive('summarizeProbeResults')
            ->with($results)
            ->once()
            ->andReturn($summary);

        $this->analyzeHandlerMock->shouldReceive('extractDateFromFilename')
            ->with('path/to/test@2023-01-01.json')
            ->once()
            ->andReturn('2023-01-01');

        $this->analyzeHandlerMock->shouldReceive('saveAnalyzedResults')
            ->with($project, Mockery::on(function($arg) {
                return isset($arg['example_com']['2023-01-01']) && $arg['example_com']['2023-01-01']['url'] === 'http://example.com';
            }))
            ->once();

        $this->artisan('uplinkr:project:analyze', ['--project' => $project])
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests the command with date filters.
     */
    public function test_analyze_project_with_dates(): void
    {
        $project = 'test-project';
        $from = '2023-01-01';
        $to = '2023-01-31';

        $this->analyzeHandlerMock->shouldReceive('probeResultsList')
            ->with($project, $from, $to)
            ->once()
            ->andReturn([]);

        $this->artisan('uplinkr:project:analyze', [
            '--project' => $project,
            '--from' => $from,
            '--to' => $to
        ])->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests the command when no files are found.
     */
    public function test_analyze_project_no_files_found(): void
    {
        $project = 'empty-project';

        $this->analyzeHandlerMock->shouldReceive('probeResultsList')
            ->with($project, null, null)
            ->once()
            ->andReturn([]);

        $this->artisan('uplinkr:project:analyze', ['--project' => $project])
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
