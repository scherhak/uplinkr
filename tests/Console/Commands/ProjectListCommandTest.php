<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Tests\TestCase;

class ProjectListCommandTest extends TestCase
{
    private MockInterface|ListHandler $listHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listHandlerMock = Mockery::mock(ListHandler::class);
        $this->app->instance(ListHandler::class, $this->listHandlerMock);
    }

    /**
     * Test that the command lists all projects with details.
     */
    public function test_it_lists_all_projects_with_details(): void
    {
        $projects = [
            [
                'project' => 'project1',
                'label' => 'Project One',
                'status' => 'enabled',
                'description' => 'Project one description',
                'alerts' => [[
                    'enabled' => true,
                    'trigger_after_failures' => 5,
                    'cooldown_minutes' => 60,
                    'latency_threshold_ms' => 1000,
                    'trigger_after_slow' => 10,
                    'channels' => ['mail', 'log'],
                ]],
                'probes' => [
                    ['method' => 'GET', 'url' => 'https://example.com/health'],
                    ['method' => 'POST', 'url' => 'https://example.com/status'],
                ],
                'state' => [
                    'total_failures' => 12,
                    'last_notification_at' => '2026-02-28 05:15:02',
                ],
            ],
        ];

        $this->listHandlerMock->shouldReceive('allWithDetails')
            ->once()
            ->andReturn($projects);

        $this->artisan('uplinkr:project:list')
            ->expectsOutput('Project: project1 | Project One | enabled')
            ->expectsOutput('Description: Project one description')
            ->expectsOutput('Alerts: enabled=true, trigger_after_failures=5, cooldown_minutes=60, latency_threshold_ms=1000, trigger_after_slow=10, channels=mail,log')
            ->expectsTable(
                ['Method', 'URL'],
                [
                    ['GET', 'https://example.com/health'],
                    ['POST', 'https://example.com/status'],
                ]
            )
            ->expectsOutput('State: total_failures=12, last_notification=2026-02-28 05:15:02')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Test that the command works correctly when there are no projects.
     */
    public function test_it_handles_no_projects(): void
    {
        $this->listHandlerMock->shouldReceive('allWithDetails')
            ->once()
            ->andReturn([]);

        $this->artisan('uplinkr:project:list')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_filters_by_project_option(): void
    {
        $projects = [
            [
                'project' => 'project1',
                'label' => 'Project One',
                'status' => 'enabled',
                'description' => null,
                'alerts' => [],
                'probes' => [],
                'state' => ['total_failures' => 0, 'last_notification_at' => null],
            ],
            [
                'project' => 'project2',
                'label' => 'Project Two',
                'status' => 'disabled',
                'description' => null,
                'alerts' => [],
                'probes' => [],
                'state' => ['total_failures' => 0, 'last_notification_at' => null],
            ],
        ];

        $this->listHandlerMock->shouldReceive('allWithDetails')
            ->once()
            ->andReturn($projects);

        $this->artisan('uplinkr:project:list', ['--project' => 'project2'])
            ->expectsOutput('Project: project2 | Project Two | disabled')
            ->doesntExpectOutput('Project: project1 | Project One | enabled')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    public function test_it_returns_error_when_selected_project_does_not_exist(): void
    {
        $this->listHandlerMock->shouldReceive('allWithDetails')
            ->once()
            ->andReturn([
                [
                    'project' => 'project1',
                    'label' => 'Project One',
                    'status' => 'enabled',
                    'description' => null,
                    'alerts' => [],
                    'probes' => [],
                    'state' => ['total_failures' => 0, 'last_notification_at' => null],
                ],
            ]);

        $this->artisan('uplinkr:project:list', ['--project' => 'missing-project'])
            ->expectsOutput('Project "missing-project" not found. Please check the spelling.')
            ->expectsOutput('Available projects:')
            ->expectsOutput('- project1')
            ->assertExitCode(CommandAlias::FAILURE);
    }
}
