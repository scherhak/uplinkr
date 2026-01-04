<?php

namespace Uplinkr\Tests\Console\Commands;

use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Project\ArchiveHandler;
use Uplinkr\Tests\TestCase;

class ProjectArchiveCommandTest extends TestCase
{
    private MockInterface|ArchiveHandler $archiveHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->archiveHandlerMock = Mockery::mock(ArchiveHandler::class);
        $this->app->instance(ArchiveHandler::class, $this->archiveHandlerMock);
    }

    /**
     * Test successful archiving with confirmation.
     */
    public function test_archive_project_success_with_confirmation(): void
    {
        $project = 'test-project';

        $this->archiveHandlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->archiveHandlerMock->shouldReceive('archive')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->artisan('uplinkr:project:archive', ['--project' => $project])
            ->expectsConfirmation(__('uplinkr::messages.project_archive_start', ['project' => $project]), 'yes')
            ->expectsOutput(__('uplinkr::messages.project_archive_success', ['project' => $project]))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Test successful archiving with --force option.
     */
    public function test_archive_project_success_with_force(): void
    {
        $project = 'test-project';

        $this->archiveHandlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->archiveHandlerMock->shouldReceive('archive')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->artisan('uplinkr:project:archive', ['--project' => $project, '--force' => true])
            ->assertExitCode(CommandAlias::SUCCESS);

        // Ensure no confirmation was asked and no success message was shown due to !force check in code
        // Wait, looking at the code:
        // if (!$force) { $this->info(__('uplinkr::messages.project_archive_success', ...)); }
        // So with --force, there is no output.
    }

    /**
     * Test archiving canceled by user.
     */
    public function test_archive_project_canceled(): void
    {
        $project = 'test-project';

        $this->archiveHandlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->archiveHandlerMock->shouldNotReceive('archive');

        $this->artisan('uplinkr:project:archive', ['--project' => $project])
            ->expectsConfirmation(__('uplinkr::messages.project_archive_start', ['project' => $project]), 'no')
            ->assertExitCode(CommandAlias::INVALID);
    }

    /**
     * Test archiving fails when project does not exist.
     */
    public function test_archive_project_not_found(): void
    {
        $project = 'non-existent-project';

        $this->archiveHandlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnFalse();

        $this->artisan('uplinkr:project:archive', ['--project' => $project])
            ->expectsOutput(__('uplinkr::messages.project_not_found', ['project' => $project]))
            ->assertExitCode(CommandAlias::INVALID);
    }

    /**
     * Test archiving fails when handler returns false.
     */
    public function test_archive_project_failed_handler(): void
    {
        $project = 'test-project';

        $this->archiveHandlerMock->shouldReceive('exists')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->archiveHandlerMock->shouldReceive('archive')
            ->with($project)
            ->once()
            ->andReturnFalse();

        $this->artisan('uplinkr:project:archive', ['--project' => $project])
            ->expectsConfirmation(__('uplinkr::messages.project_archive_start', ['project' => $project]), 'yes')
            ->expectsOutput(__('uplinkr::messages.project_archive_failed', ['project' => $project]))
            ->assertExitCode(CommandAlias::INVALID);
    }
}
