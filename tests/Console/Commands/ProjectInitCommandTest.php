<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Uplinkr\Handler\Project\InitHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class ProjectInitCommandTest extends TestCase
{
    public function test_it_initializes_project_with_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);

        $storageMock->shouldReceive('findProject')
            ->twice()
            ->with('my-project')
            ->andReturnNull();

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['project'] === 'my-project' &&
                       $data['label'] === 'My Label' &&
                       $data['description'] === 'My Description';
            }));

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:init', [
            '--project' => 'my-project',
            '--label' => 'My Label',
            '--description' => 'My Description',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_it_asks_for_confirmation_without_force(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);

        $storageMock->shouldReceive('findProject')
            ->twice()
            ->with('my-project')
            ->andReturnNull();

        $storageMock->shouldReceive('saveProject')
            ->once();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:init', [
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should project my-project be created and initialized now?', 'yes')
            ->assertExitCode(0);
    }

    public function test_it_aborts_without_confirmation(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $storageMock->shouldReceive('findProject')
            ->andReturnNull();

        $this->app->instance(ProjectStorageInterface::class, $storageMock);

        $this->artisan('uplinkr:project:init', [
            '--project' => 'my-project',
        ])
            ->expectsConfirmation('Should project my-project be created and initialized now?', 'no')
            ->assertExitCode(2);
    }

    public function test_it_fails_validation_without_project(): void
    {
        $this->artisan('uplinkr:project:init', [
            '--force' => true,
        ])->assertExitCode(2);
    }
}
