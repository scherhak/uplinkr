<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProjectAddProbeCommandTest extends TestCase
{
    public function test_it_adds_probe_with_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('addToProject')
            ->once()
            ->with([
                'url' => 'https://example.com',
                'project' => 'my-project',
                'method' => 'GET',
                'headers' => [],
                'body' => null,
                'latency' => null,
            ]);

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:add:probe', [
            '--url' => 'https://example.com',
            '--project' => 'my-project',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('addToProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:add:probe', [
            '--url' => 'https://example.com',
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should URL https://example.com be added to or updated in project my-project now?', 'yes')
            ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $this->artisan('uplinkr:project:add:probe', [
            '--url' => 'https://example.com',
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should URL https://example.com be added to or updated in project my-project now?', 'no')
            ->assertExitCode(2);
    }

    public function test_it_fails_validation_without_required_options(): void
    {
        $this->artisan('uplinkr:project:add:probe', [
            '--project' => 'my-project', // missing url
            '--force' => true,
        ])->assertExitCode(2);

        $this->artisan('uplinkr:project:add:probe', [
            '--url' => 'https://example.com', // missing project
            '--force' => true,
        ])->assertExitCode(2);
    }

    public function test_it_handles_complex_options(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('addToProject')
            ->once()
            ->with([
                'url' => 'https://example.com/api',
                'project' => 'api-project',
                'method' => 'POST',
                'headers' => ['Authorization: Bearer token', 'Content-Type: application/json'],
                'body' => '{"foo":"bar"}',
                'latency' => 5000,
            ]);

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:add:probe', [
            '--url' => 'https://example.com/api',
            '--project' => 'api-project',
            '--method' => 'POST',
            '--header' => ['Authorization: Bearer token', 'Content-Type: application/json'],
            '--body' => '{"foo":"bar"}',
            '--latency' => 5000,
            '--force' => true,
        ])->assertExitCode(0);
    }
}
