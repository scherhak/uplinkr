<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Handler\Project\ProbeAllProjectsHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProbeAllProjectsHandlerTest extends TestCase
{
    public function test_handle_executes_probes_for_all_projects(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $urlHandlerMock = Mockery::mock(UrlHandler::class);

        $projects = [
            [
                'project' => 'project1',
                'probes' => [
                    ['url' => 'https://example.com/1', 'method' => 'GET'],
                ],
            ],
            [
                'project' => 'project2',
                'probes' => [
                    ['url' => 'https://example.com/2', 'method' => 'POST', 'headers' => ['Auth: None'], 'body' => '{"key":"val"}'],
                ],
            ],
            [
                'project' => 'project3',
                // No probes
            ]
        ];

        $storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn($projects);

        // Expectation for project1 probe
        $urlHandlerMock->shouldReceive('with')
            ->once()
            ->with([
                'url' => 'https://example.com/1',
                'project' => 'project1',
                'method' => 'GET',
                'headers' => [],
                'body' => '',
            ])
            ->andReturnSelf();

        // Expectation for project2 probe
        $urlHandlerMock->shouldReceive('with')
            ->once()
            ->with([
                'url' => 'https://example.com/2',
                'project' => 'project2',
                'method' => 'POST',
                'headers' => ['Auth: None'],
                'body' => '{"key":"val"}',
            ])
            ->andReturnSelf();

        $urlHandlerMock->shouldReceive('handle')
            ->twice()
            ->andReturn(['probe_status' => 'reachable']);

        $handler = new ProbeAllProjectsHandler($storageMock, $urlHandlerMock);
        $results = $handler->handle();

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('project1', $results);
        $this->assertArrayHasKey('project2', $results);
        $this->assertArrayHasKey('project3', $results);
        $this->assertEmpty($results['project3']);
    }

    public function test_handle_skips_disabled_projects(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $urlHandlerMock = Mockery::mock(UrlHandler::class);

        $projects = [
            [
                'project' => 'enabled_project',
                'status' => 'enabled',
                'probes' => [
                    ['url' => 'https://example.com/1', 'method' => 'GET'],
                ],
            ],
            [
                'project' => 'disabled_project',
                'status' => 'disabled',
                'probes' => [
                    ['url' => 'https://example.com/2', 'method' => 'GET'],
                ],
            ],
        ];

        $storageMock->shouldReceive('allProjects')
            ->once()
            ->andReturn($projects);

        $urlHandlerMock->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['project'] === 'enabled_project';
            }))
            ->andReturnSelf();

        $urlHandlerMock->shouldReceive('handle')
            ->once()
            ->andReturn(['probe_status' => 'reachable']);

        $handler = new ProbeAllProjectsHandler($storageMock, $urlHandlerMock);
        $results = $handler->handle();

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('enabled_project', $results);
        $this->assertArrayNotHasKey('disabled_project', $results);
    }
}
