<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\EnableHandler;
use Uplinkr\Tests\TestCase;

class ProjectEnableCommandTest extends TestCase
{
    public function test_it_enables_project_with_force(): void
    {
        $handlerMock = Mockery::mock(EnableHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('my-project')
            ->andReturn(true);

        $this->app->instance(EnableHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:enable', [
            '--project' => 'my-project',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $handlerMock = Mockery::mock(EnableHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('my-project')
            ->andReturn(true);

        $this->app->instance(EnableHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:enable', [
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should project my-project be enabled now?', 'yes')
            ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $this->artisan('uplinkr:project:enable', [
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should project my-project be enabled now?', 'no')
            ->assertExitCode(2);
    }

    public function test_it_fails_if_handler_returns_false(): void
    {
        $handlerMock = Mockery::mock(EnableHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('non-existent')
            ->andReturn(false);

        $this->app->instance(EnableHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:enable', [
            '--project' => 'non-existent',
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_it_fails_validation_without_project(): void
    {
        $this->artisan('uplinkr:project:enable', [
            '--force' => true,
        ])->assertExitCode(2);
    }
}
