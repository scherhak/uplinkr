<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\AlertHandler;
use Uplinkr\Tests\TestCase;

class ProjectAlertsCommandTest extends TestCase
{
    public function test_it_updates_project_alerts_with_force(): void
    {
        $handlerMock = Mockery::mock(AlertHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with([
                'project' => 'my-project',
                'enabled' => true,
                'trigger_after_failures' => 5,
                'cooldown_minutes' => 60,
                'latency_threshold_ms' => 2000,
                'trigger_after_slow' => 5,
                'channels' => ['mail', 'slack'],
            ])
            ->andReturn(true);

        $this->app->instance(AlertHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--enabled' => 'true',
            '--failures' => 5,
            '--cooldown' => 60,
            '--threshold' => 2000,
            '--slow' => 5,
            '--channels' => 'mail,slack',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $handlerMock = Mockery::mock(AlertHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->andReturn(true);

        $this->app->instance(AlertHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
        ])
        ->expectsConfirmation('Should alert settings for project my-project be updated now?', 'yes')
        ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
        ])
        ->expectsConfirmation('Should alert settings for project my-project be updated now?', 'no')
        ->assertExitCode(2);
    }

    public function test_it_fails_validation_without_project(): void
    {
        $this->artisan('uplinkr:project:alerts', [
            '--failures' => 5,
            '--force' => true,
        ])->assertExitCode(2);
    }
}
