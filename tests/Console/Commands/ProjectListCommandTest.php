<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ManagerHandler;
use Uplinkr\Tests\TestCase;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProjectListCommandTest extends TestCase
{
    private MockInterface|ManagerHandler $managerHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerHandlerMock = Mockery::mock(ManagerHandler::class);
        $this->app->instance(ManagerHandler::class, $this->managerHandlerMock);
    }

    /**
     * Test that the command lists all projects and their probe counts.
     */
    public function test_it_lists_all_projects_with_counts(): void
    {
        $projects = [
            'project1',
            'path/to/project2'
        ];

        $this->managerHandlerMock->shouldReceive('listAll')
            ->once()
            ->andReturn($projects);

        $this->managerHandlerMock->shouldReceive('getProbesCount')
            ->with('project1')
            ->once()
            ->andReturn(5);

        $this->managerHandlerMock->shouldReceive('getProbesCount')
            ->with('path/to/project2')
            ->once()
            ->andReturn(10);

        $this->artisan('uplinkr:project:list')
            ->expectsOutput('project1 [5]')
            ->expectsOutput('project2 [10]')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Test that the command works correctly when there are no projects.
     */
    public function test_it_handles_no_projects(): void
    {
        $this->managerHandlerMock->shouldReceive('listAll')
            ->once()
            ->andReturn([]);

        $this->artisan('uplinkr:project:list')
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
