<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\UpdateHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProjectUpdateCommandTest extends TestCase
{
    public function test_it_updates_project_with_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->with('my-project')
            ->andReturn([
                'project' => 'my-project',
                'label' => 'Old Label',
                'description' => 'Old Description',
                'probes' => [],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--label' => 'New Label',
            '--description' => 'New Description',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->andReturn([
                'project' => 'my-project',
                'label' => 'Old Label',
                'probes' => [],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

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
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->with('non-existent')
            ->andReturn(null);

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

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
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->andReturn([
                'project' => 'my-project',
                'status' => 'enabled',
                'probes' => [],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:update', [
            '--project' => 'my-project',
            '--status' => 'disabled',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_updates_project_status_to_enabled(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->andReturn([
                'project' => 'my-project',
                'status' => 'disabled',
                'probes' => [],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

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
