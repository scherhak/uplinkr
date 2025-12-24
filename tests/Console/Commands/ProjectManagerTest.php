<?php

namespace Console\Commands;

// Wir benutzen Mockery für den Handler, da wir nur den Command testen wollen.
use Mockery;
use Uplinkr\Handler\Project\ManagerHandler;
use Uplinkr\Tests\TestCase;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProjectManagerTest extends TestCase
{
    private Mockery\MockInterface|ManagerHandler $handlerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->handlerMock = Mockery::mock(ManagerHandler::class);
        $this->app->instance(ManagerHandler::class, $this->handlerMock);
    }

    /**
     * Tests that the --list option correctly lists projects and their probe counts.
     */
    public function test_list_all_projects(): void
    {
        $this->handlerMock->shouldReceive('listAll')
            ->once()
            ->andReturn(['project1', 'project2']);

        $this->handlerMock->shouldReceive('getProbesCount')
            ->with('project1')
            ->once()
            ->andReturn(5);

        $this->handlerMock->shouldReceive('getProbesCount')
            ->with('project2')
            ->once()
            ->andReturn(10);

        $this->artisan('uplinkr:project', ['--list' => true])
            ->expectsOutput('project1 [5]')
            ->expectsOutput('project2 [10]')
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests archiving a project successfully after confirmation.
     */
    public function test_archive_project_successfully(): void
    {
        $project = 'test-project';

        $this->handlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->handlerMock->shouldReceive('archive')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->artisan('uplinkr:project', ['--project' => $project, '--archive' => true])
            ->expectsOutput(__('uplinkr::messages.project_archive_start', ['project' => $project]))
            ->expectsConfirmation(__('uplinkr::messages.project_archive_start', ['project' => $project]), 'yes')
            ->expectsOutput(__('uplinkr::messages.project_archive_success', ['project' => $project]))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests archiving a project with the --force flag (no confirmation).
     */
    public function test_archive_project_with_force_flag(): void
    {
        $project = 'test-project';

        $this->handlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->handlerMock->shouldReceive('archive')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->artisan('uplinkr:project', ['--project' => $project, '--archive' => true, '--force' => true])
            ->assertExitCode(CommandAlias::SUCCESS);
            // With force, messages are mostly skipped in the current implementation.
    }

    /**
     * Tests that the command aborts if the user does not confirm archiving.
     */
    public function test_archive_project_fails_if_not_confirmed(): void
    {
        $project = 'test-project';

        $this->handlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->handlerMock->shouldNotReceive('archive');

        $this->artisan('uplinkr:project', ['--project' => $project, '--archive' => true])
            ->expectsConfirmation(__('uplinkr::messages.project_archive_start', ['project' => $project]), 'no')
            ->assertExitCode(CommandAlias::INVALID);
    }

    /**
     * Tests archiving fails if the project does not exist.
     */
    public function test_archive_project_fails_if_project_not_found(): void
    {
        $project = 'missing-project';

        $this->handlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnFalse();

        $this->artisan('uplinkr:project', ['--project' => $project, '--archive' => true])
            ->expectsOutput(__('uplinkr::messages.project_not_found', ['project' => $project]))
            ->assertExitCode(CommandAlias::INVALID);
    }

    /**
     * Tests failure behavior if the handler fails to archive.
     */
    public function test_archive_fails_if_handler_returns_false(): void
    {
        $project = 'test-project';

        $this->handlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->handlerMock->shouldReceive('archive')
            ->with($project)
            ->once()
            ->andReturnFalse();

        $this->artisan('uplinkr:project', ['--project' => $project, '--archive' => true])
            ->expectsConfirmation(__('uplinkr::messages.project_archive_start', ['project' => $project]), 'yes')
            ->expectsOutput(__('uplinkr::messages.project_archive_failed', ['project' => $project]))
            ->assertExitCode(CommandAlias::INVALID);
    }

    /**
     * Tests that a warning is shown if --project is provided without --archive.
     */
    public function test_warns_if_project_missing_archive_flag(): void
    {
        $this->artisan('uplinkr:project', ['--project' => 'some-project'])
            ->expectsOutput(__('uplinkr::messages.project_archive_option_missing'))
            ->assertExitCode(CommandAlias::INVALID);
    }
}
