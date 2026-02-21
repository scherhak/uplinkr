<?php

namespace Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\Analyze\AnalyzeHandler;
use Uplinkr\Handler\Project\Analyze\SummaryHandler;
use Uplinkr\Tests\TestCase;

class AnalyzeProjectTest extends TestCase
{
    private MockInterface|AnalyzeHandler $analyzeHandlerMock;
    private SummaryHandler $summaryHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzeHandlerMock = Mockery::mock(AnalyzeHandler::class);
        $this->summaryHandler = new SummaryHandler(new \Uplinkr\Support\Sanitizer(new \Uplinkr\Objects\Config\UplinkrConfig()));

        $this->app->instance(AnalyzeHandler::class, $this->analyzeHandlerMock);
        $this->app->instance(SummaryHandler::class, $this->summaryHandler);
    }

    /**
     * Tests that the command executes successfully when results are found.
     */
    public function test_analyze_project_success(): void
    {
        $project = 'test-project';
        $files = ['path/to/test@2023-01-01.json'];
        $content = '{"some":"json"}';
        $results = [[
            'settings' => ['url' => 'http://example.com'],
            'probe_status' => 'reachable',
            'probe_message' => ['duration_ms' => 100.0],
            'executed' => 100,
            'status_header' => 200,
        ]];

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

        $this->analyzeHandlerMock->shouldReceive('extractDateFromFilename')
            ->with('path/to/test@2023-01-01.json')
            ->once()
            ->andReturn('2023-01-01');

        $this->analyzeHandlerMock->shouldReceive('saveAnalyzedResults')
            ->with($project, Mockery::on(function ($arg) {
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
