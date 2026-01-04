<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\RemoveProbeHandler;
use Uplinkr\Tests\TestCase;

class ProjectRemoveProbeCommandTest extends TestCase
{
    public function test_it_removes_probe_with_force(): void
    {
        $handlerMock = Mockery::mock(RemoveProbeHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with([
                'url' => 'http://example.com',
                'project' => 'my-project',
            ]);

        $this->app->instance(RemoveProbeHandler::class, $handlerMock);

        $this->artisan('uplinkr:project:remove:probe', [
            '--url' => 'http://example.com',
            '--project' => 'my-project',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $handlerMock = Mockery::mock(RemoveProbeHandler::class);
        $handlerMock->shouldReceive('handle')
            ->once();

        $this->app->instance(RemoveProbeHandler::class, $handlerMock);

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
