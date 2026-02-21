<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProjectRemoveProbeCommandTest extends TestCase
{
    public function test_it_removes_probe_with_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('removeFromProject')
            ->once()
            ->with([
                'url' => 'http://example.com',
                'project' => 'my-project',
            ]);

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:remove:probe', [
            '--url' => 'http://example.com',
            '--project' => 'my-project',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('removeFromProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:remove:probe', [
            '--url' => 'http://example.com',
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should URL http://example.com be removed from project my-project now?', 'yes')
            ->assertExitCode(0);
    }

    public function test_it_fails_with_invalid_url(): void
    {
        $this->artisan('uplinkr:project:remove:probe', [
            '--url' => 'invalid-url',
            '--project' => 'my-project',
            '--force' => true,
        ])->assertExitCode(2);
    }
}
