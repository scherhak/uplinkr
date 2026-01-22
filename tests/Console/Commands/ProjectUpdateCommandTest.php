<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\UpdateHandler;
use Uplinkr\Tests\TestCase;

class ProjectUpdateCommandTest extends TestCase
{
    public function test_it_updates_project_with_force(): void
    {
        $handlerMock = Mockery::mock(UpdateHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with([
                'project' => 'my-project',
                'label' => 'New Label',
                'description' => 'New Description',
                'status' => null,
            ])
            ->andReturn(true);

        $this->app->instance(UpdateHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--label' => 'New Label',
            '--description' => 'New Description',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $handlerMock = Mockery::mock(UpdateHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->andReturn(true);

        $this->app->instance(UpdateHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--label' => 'New Label',
        ])
            ->expectsConfirmation('Should project my-project be updated now?', 'yes')
            ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should project my-project be updated now?', 'no')
            ->assertExitCode(2);
    }

    public function test_it_fails_if_handler_returns_false(): void
    {
        $handlerMock = Mockery::mock(UpdateHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->andReturn(false);

        $this->app->instance(UpdateHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'non-existent',
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_it_fails_validation_without_project(): void
    {
        $this->artisan('uplinkr:project:update', [
            '--label' => 'New Label',
            '--force' => true,
        ])->assertExitCode(2);
    }

    public function test_it_updates_project_status_to_disabled(): void
    {
        $handlerMock = Mockery::mock(UpdateHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with([
                'project' => 'my-project',
                'label' => null,
                'description' => null,
                'status' => 'disabled',
            ])
            ->andReturn(true);

        $this->app->instance(UpdateHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--status' => 'disabled',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_updates_project_status_to_enabled(): void
    {
        $handlerMock = Mockery::mock(UpdateHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with([
                'project' => 'my-project',
                'label' => null,
                'description' => null,
                'status' => 'enabled',
            ])
            ->andReturn(true);

        $this->app->instance(UpdateHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--status' => 'enabled',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_fails_validation_with_invalid_status(): void
    {
        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--status' => 'invalid-status',
            '--force' => true,
        ])->assertExitCode(2);
    }
}
