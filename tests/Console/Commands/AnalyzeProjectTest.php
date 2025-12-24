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
        $files = ['path/to/file1.log'];
        $content = '{"some":"json"}';
        $results = [['url' => 'http://example.com']];
        $summary = ['http://example.com' => ['info']];

        $this->analyzeHandlerMock->shouldReceive('probeResultsList')
            ->with($project, null, null)
            ->once()
            ->andReturn($files);

        $this->analyzeHandlerMock->shouldReceive('readProbeResultFile')
            ->with('path/to/file1.log')
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

        // We check if Log::debug is called as the command logs the results.
        Log::shouldReceive('debug')->atLeast()->once();

        $this->artisan('uplinkr:analyze', ['--project' => $project])
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

        $this->artisan('uplinkr:analyze', [
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

        $this->artisan('uplinkr:analyze', ['--project' => $project])
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
