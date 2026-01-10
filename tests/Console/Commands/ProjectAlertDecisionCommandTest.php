<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\Alerts\AlertDecisionHandler;
use Uplinkr\Tests\TestCase;

class ProjectAlertDecisionCommandTest extends TestCase
{
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
            ->expectsOutput('No alerts triggered for project "my-project".')
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
            ->expectsOutput('Found 2 alert decision(s) for project "my-project":')
            ->expectsOutput(' - Project: my-project | Probe: GET https://example.com | Reason: consecutive_failures | Count: 5')
            ->expectsOutput(' - Project: my-project | Probe: POST https://api.example.com | Reason: consecutive_failures | Count: 10')
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
            ->expectsOutput('Found 2 alert decision(s) across all projects:')
            ->expectsOutput(' - Project: project-a | Probe: GET https://a.com | Reason: consecutive_failures | Count: 3')
            ->expectsOutput(' - Project: project-b | Probe: GET https://b.com | Reason: consecutive_failures | Count: 4')
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
            ->expectsOutput('No alerts triggered for any project.')
            ->assertExitCode(0);
    }
}
