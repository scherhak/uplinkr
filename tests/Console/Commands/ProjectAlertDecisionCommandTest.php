<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\Alerts\AlertDecisionHandler;
use Uplinkr\Support\CliIcon;
use Uplinkr\Tests\TestCase;
use Illuminate\Support\Facades\Log;

class ProjectAlertDecisionCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $loggerMock = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $loggerMock->shouldReceive('warning')->withAnyArgs();
        Log::shouldReceive('channel')->andReturn($loggerMock);
    }

    public function test_it_shows_no_alerts_message_when_decisions_are_empty(): void
    {
        $handlerMock = Mockery::mock(AlertDecisionHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('my-project')
            ->andReturn([]);

        $this->app->instance(AlertDecisionHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alert:decision', [
            '--project' => 'my-project',
        ])
            ->expectsOutput(CliIcon::ERROR->label(__('uplinkr::messages.project_alerts_decisions_none_project', ['project' => 'my-project'])))
            ->assertExitCode(0);
    }

    public function test_it_shows_alerts_when_decisions_found(): void
    {
        $handlerMock = Mockery::mock(AlertDecisionHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('my-project')
            ->andReturn([
                [
                    'project' => 'my-project',
                    'probe' => 'GET https://example.com',
                    'reason' => 'consecutive_failures',
                    'count' => 5
                ],
                [
                    'project' => 'my-project',
                    'probe' => 'POST https://api.example.com',
                    'reason' => 'consecutive_failures',
                    'count' => 10
                ]
            ]);

        $this->app->instance(AlertDecisionHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alert:decision', [
            '--project' => 'my-project',
        ])
            ->expectsOutput(__('uplinkr::messages.project_alerts_decisions_found_project', ['count' => 2, 'project' => 'my-project']))
            ->assertExitCode(0);
    }

    public function test_it_handles_no_project_parameter(): void
    {
        $handlerMock = Mockery::mock(AlertDecisionHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with(null)
            ->andReturn([
                [
                    'project' => 'project-a',
                    'probe' => 'GET https://a.com',
                    'reason' => 'consecutive_failures',
                    'count' => 3
                ],
                [
                    'project' => 'project-b',
                    'probe' => 'GET https://b.com',
                    'reason' => 'consecutive_failures',
                    'count' => 4
                ]
            ]);

        $this->app->instance(AlertDecisionHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alert:decision')
            ->expectsOutput(__('uplinkr::messages.project_alerts_decisions_found_all', ['count' => 2]))
            ->assertExitCode(0);
    }

    public function test_it_shows_no_alerts_message_when_no_project_and_no_decisions(): void
    {
        $handlerMock = Mockery::mock(AlertDecisionHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with(null)
            ->andReturn([]);

        $this->app->instance(AlertDecisionHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alert:decision')
            ->expectsOutput(CliIcon::ERROR->label(__('uplinkr::messages.project_alerts_decisions_none_all')))
            ->assertExitCode(0);
    }
}
