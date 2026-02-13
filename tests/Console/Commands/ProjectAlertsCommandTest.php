<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\Alerts\AlertHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProjectAlertsCommandTest extends TestCase
{
    public function test_it_updates_project_alerts_with_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->with('my-project')
            ->andReturn([
                'project' => 'my-project',
                'alerts' => [],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--enabled' => 'true',
            '--failures' => 5,
            '--cooldown' => 60,
            '--threshold' => 2000,
            '--slow' => 5,
            '--channels' => 'mail,webhook',
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
                'alerts' => [],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--failures' => 5,
            '--cooldown' => 60,
            '--threshold' => 2000,
            '--slow' => 5,
        ])
            ->expectsConfirmation('Should alert settings for project my-project be updated now?', 'yes')
            ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--failures' => 5,
            '--cooldown' => 60,
            '--threshold' => 2000,
            '--slow' => 5,
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
