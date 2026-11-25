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
            '--certificate-expiration' => true,
            '--certificate-warning-days' => '30,14,7,3,1',
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

    public function test_it_fails_validation_for_invalid_certificate_warning_days(): void
    {
        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--certificate-warning-days' => '30,invalid,0',
            '--force' => true,
        ])->assertExitCode(2);
    }

    public function test_it_disables_certificate_expiration_alerts(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->once()
            ->with('my-project')
            ->andReturn([
                'project' => 'my-project',
                'alerts' => [['certificate_expiration_enabled' => true]],
            ]);
        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(static fn(array $project): bool =>
                $project['alerts'][0]['certificate_expiration_enabled'] === false
            ));

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--no-certificate-expiration' => true,
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_rejects_conflicting_certificate_expiration_flags(): void
    {
        $this->artisan('uplinkr:project:alerts', [
            '--project' => 'my-project',
            '--certificate-expiration' => true,
            '--no-certificate-expiration' => true,
            '--force' => true,
        ])->assertExitCode(2);
    }
}
