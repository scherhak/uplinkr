<?php

namespace Uplinkr\Tests\Console\Commands;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Handler\Storage\PruneHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

/**
 * Class StoragePruneTest
 * @package Uplinkr\Tests\Commands
 */
class StoragePruneTest extends TestCase
{
    private MockInterface $handlerMock;

    /**
     * Sets up the required environment for the tests.
     * Initializes a real configuration instance for consistency and binds it into the application container.
     * Mocks the handler for verifying method calls during the tests.
     *
     * @return void
     */
    #[Test]
    protected function setUp(): void
    {
        parent::setUp();

        // 1. We create a real config to keep paths consistent
        $config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            standardProject: 'test_project',
            probeResultsPath: 'probes',
            probeFilenameSeparator: '@',
            fileExtension: 'log',
            archivedFolder: 'archived',
            allowCompleteWipe: true,
        );

        // We bind the config into the container
        $this->app->instance(UplinkrConfig::class, $config);

        // 2. We mock the handler to verify if methods are called
        $this->handlerMock = Mockery::mock(PruneHandler::class);
        $this->app->instance(PruneHandler::class, $this->handlerMock);
    }

    /**
     * Tests: prune --project=X --before=Y --force
     * Scenario: Successfully deleting files before a date.
     */
    #[Test]
    public function test_prune_files_before_date_executed_successfully(): void
    {
        $project = 'my-project';
        $date = '2023-01-01';

        // Expectation: The handler is called exactly once with the correct parameters
        $this->handlerMock
            ->shouldReceive('pruneBeforeDate')
            ->once()
            ->with($project, $date)
            ->andReturn(5); // Simulates 5 deleted files

        $this->artisan('uplinkr:prune', [
            '--project' => $project,
            '--before' => $date,
            '--force' => true,
        ])
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests: prune --project=X --before=INVALID --force
     * Scenario: Handler throws exception due to invalid date.
     */
    public function test_prune_files_before_date_handles_invalid_date_exception(): void
    {
        $project = 'my-project';
        $invalidDate = 'not-a-date';

        // Expectation: Handler throws exception
        $this->handlerMock
            ->shouldReceive('pruneBeforeDate')
            ->once()
            ->with($project, $invalidDate)
            ->andThrow(new InvalidArgumentException('Invalid date format'));

        $this->artisan('uplinkr:prune', [
            '--project' => $project,
            '--before' => $invalidDate,
            '--force' => true,
        ])
            ->assertExitCode(CommandAlias::FAILURE); // Command should return failure exit code
    }

    /**
     * Tests: prune --project=X --force (without --before)
     * Scenario: The entire project folder is deleted.
     * Note: The command does this directly via Storage::deleteDirectory, not via the handler.
     */
    public function test_prune_project_folder_deletes_entire_directory(): void
    {
        Storage::fake('local');
        $project = 'delete-me';
        $path = "uplinkr/{$project}";

        // Create directory
        Storage::disk('local')->makeDirectory($path);
        Storage::disk('local')->assertExists($path);

        // Handler Mock Setup (should not be called for pruneBeforeDate here)
        $this->handlerMock->shouldNotReceive('pruneBeforeDate');

        $this->artisan('uplinkr:prune', [
            '--project' => $project,
            '--force' => true,
        ])
            ->assertExitCode(CommandAlias::SUCCESS);

        // Assert: Directory is gone
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Tests: prune --project=X --force
     * Scenario: Project folder does not exist.
     */
    public function test_prune_project_folder_warns_if_folder_missing(): void
    {
        Storage::fake('local');
        $project = 'ghost-project';

        // We DO NOT create the folder

        $this->artisan('uplinkr:prune', [
            '--project' => $project,
            '--force' => true, // Force usually suppresses errors, but the ExitCode remains SUCCESS
        ])
            ->assertExitCode(CommandAlias::SUCCESS);

        // If we omit force, we would expect an error message
        $this->artisan('uplinkr:prune', [
            '--project' => $project,
        ])
            ->expectsConfirmation(__('uplinkr::messages.prune_project', ['project' => $project]), 'yes')
            ->expectsOutputToContain(__('uplinkr::messages.prune_project_folder_does_not_exists', ['project' => $project]))
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests: prune --wipe-all --force
     * Scenario: Everything is deleted and recreated.
     */
    public function test_wipe_all_resets_storage_directory(): void
    {
        // Expectation: deleteDirectory and makeDirectory are called on the handler
        $this->handlerMock
            ->shouldReceive('deleteDirectory')
            ->once()
            ->with('uplinkr'); // Path from config

        $this->handlerMock
            ->shouldReceive('makeDirectory')
            ->once()
            ->with('uplinkr');

        $this->artisan('uplinkr:prune', [
            '--wipe-all' => true,
            '--force' => true,
        ])
            ->assertExitCode(CommandAlias::SUCCESS);
    }

    /**
     * Tests: Aborting confirmation (without Force).
     */
    public function test_command_aborts_if_not_confirmed(): void
    {
        $project = 'my-project';

        // Expectation: Handler is NOT called
        $this->handlerMock->shouldNotReceive('pruneBeforeDate');

        $this->artisan('uplinkr:prune', [
            '--project' => $project,
        ])
            ->expectsConfirmation(__('uplinkr::messages.prune_project', ['project' => $project]), 'no')
            ->assertExitCode(CommandAlias::INVALID);
    }

    /**
     * Tests: Call without valid parameters
     */
    public function test_command_does_nothing_without_arguments_and_warns(): void
    {
        // If nothing is passed, it asks for the project name (which is null/empty)
        // If we say 'yes', it lands in the "else" block for "no files wiped"

        $this->artisan('uplinkr:prune')
            ->expectsConfirmation(__('uplinkr::messages.prune_project', ['project' => null]), 'yes')
            ->expectsOutputToContain(__('uplinkr::messages.prune_wipe_all_no_files_wiped'))
            ->assertExitCode(CommandAlias::SUCCESS);
    }
}
