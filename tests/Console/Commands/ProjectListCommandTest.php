<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Tests\TestCase;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProjectListCommandTest extends TestCase
{
    private MockInterface|ListHandler $listHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listHandlerMock = Mockery::mock(ListHandler::class);
        $this->app->instance(ListHandler::class, $this->listHandlerMock);
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

        $this->listHandlerMock->shouldReceive('all')
            ->once()
            ->andReturn($projects);

        $this->listHandlerMock->shouldReceive('countProbes')
            ->with('project1')
            ->once()
            ->andReturn(5);

        $this->listHandlerMock->shouldReceive('countProbes')
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
        $this->listHandlerMock->shouldReceive('all')
            ->once()
            ->andReturn([]);

        $this->artisan('uplinkr:project:list')
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
