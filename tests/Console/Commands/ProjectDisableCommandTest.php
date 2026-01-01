<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\DisableHandler;
use Uplinkr\Tests\TestCase;

class ProjectDisableCommandTest extends TestCase
{
    public function test_it_disables_project_with_force(): void
    {
        $handlerMock = Mockery::mock(DisableHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('my-project')
            ->andReturn(true);

        $this->app->instance(DisableHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:disable', [
            '--project' => 'my-project',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $handlerMock = Mockery::mock(DisableHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('my-project')
            ->andReturn(true);

        $this->app->instance(DisableHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:disable', [
            '--project' => 'my-project',
        ])
        ->expectsConfirmation('Should project my-project be disabled now?', 'yes')
        ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $this->artisan('uplinkr:project:disable', [
            '--project' => 'my-project',
        ])
        ->expectsConfirmation('Should project my-project be disabled now?', 'no')
        ->assertExitCode(2);
    }

    public function test_it_fails_if_handler_returns_false(): void
    {
        $handlerMock = Mockery::mock(DisableHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with('non-existent')
            ->andReturn(false);

        $this->app->instance(DisableHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:disable', [
            '--project' => 'non-existent',
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_it_fails_validation_without_project(): void
    {
        $this->artisan('uplinkr:project:disable', [
            '--force' => true,
        ])->assertExitCode(2);
    }
}
