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

        $this->artisan('uplinkr:project:alert-decision', [
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
                    'probe' => 'GET https://example.com',
                    'reason' => 'consecutive_failures',
                    'count' => 5
                ],
                [
                    'probe' => 'POST https://api.example.com',
                    'reason' => 'consecutive_failures',
                    'count' => 10
                ]
            ]);

        $this->app->instance(AlertDecisionHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alert-decision', [
            '--project' => 'my-project',
        ])
            ->expectsOutput('Found 2 alert decision(s) for project "my-project":')
            ->expectsOutput(' - Probe: GET https://example.com | Reason: consecutive_failures | Count: 5')
            ->expectsOutput(' - Probe: POST https://api.example.com | Reason: consecutive_failures | Count: 10')
            ->assertExitCode(0);
    }

    public function test_it_fails_validation_without_project(): void
    {
        $this->artisan('uplinkr:project:alert-decision')
            ->expectsOutput('Validation failed. Please provide a project name.')
            ->assertExitCode(2);
    }
}
