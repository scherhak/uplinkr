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
            ->expectsOutput(__('messages.project_alerts_decisions_none_project', ['project' => 'my-project']))
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
            ->expectsOutput(__('messages.project_alerts_decisions_found_project', ['count' => 2, 'project' => 'my-project']))
            ->expectsOutput(__('messages.project_alerts_decisions_list_item', [
                'project' => '<fg=magenta>my-project</>',
                'probe' => '<fg=cyan>GET https://example.com</>',
                'reason' => '<fg=yellow>consecutive_failures</>',
                'count' => '<fg=red>5</>'
            ]))
            ->expectsOutput(__('messages.project_alerts_decisions_list_item', [
                'project' => '<fg=magenta>my-project</>',
                'probe' => '<fg=cyan>POST https://api.example.com</>',
                'reason' => '<fg=yellow>consecutive_failures</>',
                'count' => '<fg=red>10</>'
            ]))
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
            ->expectsOutput(__('messages.project_alerts_decisions_found_all', ['count' => 2]))
            ->expectsOutput(__('messages.project_alerts_decisions_list_item', [
                'project' => '<fg=magenta>project-a</>',
                'probe' => '<fg=cyan>GET https://a.com</>',
                'reason' => '<fg=yellow>consecutive_failures</>',
                'count' => '<fg=red>3</>'
            ]))
            ->expectsOutput(__('messages.project_alerts_decisions_list_item', [
                'project' => '<fg=magenta>project-b</>',
                'probe' => '<fg=cyan>GET https://b.com</>',
                'reason' => '<fg=yellow>consecutive_failures</>',
                'count' => '<fg=red>4</>'
            ]))
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
            ->expectsOutput(__('messages.project_alerts_decisions_none_all'))
            ->assertExitCode(0);
    }
}
