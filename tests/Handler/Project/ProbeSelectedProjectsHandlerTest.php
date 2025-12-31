<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Handler\Project\ProbeSelectedProjectsHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProbeSelectedProjectsHandlerTest extends TestCase
{
    public function test_handle_executes_probes_for_selected_project(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $urlHandlerMock = Mockery::mock(UrlHandler::class);

        $projectName = 'test-project';
        $project = [
            'project' => $projectName,
            'probes' => [
                ['url' => 'https://example.com/1', 'method' => 'GET'],
                ['url' => 'https://example.com/2', 'method' => 'POST', 'headers' => ['X-Header: Value'], 'body' => '{"foo":"bar"}'],
            ],
        ];

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($project);

        // Expectation for first probe
        $urlHandlerMock->shouldReceive('with')
            ->once()
            ->with([
                'url' => 'https://example.com/1',
                'project' => $projectName,
                'method' => 'GET',
                'headers' => [],
                'body' => '',
            ])
            ->andReturnSelf();

        // Expectation for second probe
        $urlHandlerMock->shouldReceive('with')
            ->once()
            ->with([
                'url' => 'https://example.com/2',
                'project' => $projectName,
                'method' => 'POST',
                'headers' => ['X-Header: Value'],
                'body' => '{"foo":"bar"}',
            ])
            ->andReturnSelf();

        $urlHandlerMock->shouldReceive('handle')
            ->twice()
            ->andReturn(['probe_status' => 'reachable']);

        $handler = new ProbeSelectedProjectsHandler($storageMock, $urlHandlerMock);
        $results = $handler->handle($projectName);

        $this->assertCount(2, $results);
        $this->assertEquals(['probe_status' => 'reachable'], $results[0]);
    }

    public function test_handle_returns_null_if_project_not_found(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $urlHandlerMock = Mockery::mock(UrlHandler::class);

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with('non-existent')
            ->andReturn(null);

        $handler = new ProbeSelectedProjectsHandler($storageMock, $urlHandlerMock);
        $result = $handler->handle('non-existent');

        $this->assertNull($result);
    }

    public function test_handle_returns_empty_array_if_no_probes(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $urlHandlerMock = Mockery::mock(UrlHandler::class);

        $projectName = 'empty-project';
        $project = [
            'project' => $projectName,
            'probes' => [],
        ];

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($project);

        $handler = new ProbeSelectedProjectsHandler($storageMock, $urlHandlerMock);
        $result = $handler->handle($projectName);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
