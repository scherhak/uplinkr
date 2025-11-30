<?php

namespace Uplinkr\Tests\Handler;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Uplinkr\Handler\StoragePruneHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class StoragePruneHandlerTest extends TestCase
{
    private UplinkrConfig $config;

    /**
     * Sets up the test environment for the test case.
     * This method initializes the UplinkrConfig object with pre-defined parameters
     * to configure storage settings, project standards, and file properties.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Since UplinkrConfig is 'final', we create a real instance.
        // Because we use TestCase from Testbench/Orchestra, we can pass parameters.
        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            standardProject: 'standard_project', // Important for Storage::fake()
            probeResultsPath: 'probes',
            probeFilenameSeparator: '@',
            fileExtension: 'log'
        );
    }

    /**
     * Tests the creation of a StoragePruneHandler instance with a valid configuration.
     * This method verifies that the handler is correctly instantiated using the provided configuration.
     *
     * @return void
     */
    public function test_construct_with_valid_config(): void
    {
        $handler = new StoragePruneHandler($this->config);
        $this->assertInstanceOf(StoragePruneHandler::class, $handler);
    }

    /**
     * Tests that the pruneBeforeDate method in the StoragePruneHandler deletes
     * files older than a specified date while retaining newer files.
     *
     * This method sets up a simulated storage environment to verify the functionality
     * of file pruning based on date criteria. It creates mock files representing
     * dates before and after the pruning threshold, ensures their existence,
     * invokes the pruning operation, and asserts the correct deletions were made
     * without impacting unaffected files.
     *
     * @return void
     */
    public function test_prune_before_date_deletes_old_files(): void
    {
        $project = 'test-project';
        $oldDate = '2022-01-01';
        $newDate = '2024-01-01';
        $pruneDate = '2023-01-01';

        // Path structure based on UplinkrConfig defaults
        // Path: storagePath/project/probes/
        $basePath = "uplinkr/{$project}/probes";

        Storage::fake('local');

        // Simulate filenames: ID@DATE.log
        $fileOld = "{$basePath}/old-id@{$oldDate}.log";
        $fileNew = "{$basePath}/new-id@{$newDate}.log";

        // Create files
        Storage::disk('local')->put($fileOld, 'content');
        Storage::disk('local')->put($fileNew, 'content');

        // Ensure they exist
        Storage::disk('local')->assertExists($fileOld);
        Storage::disk('local')->assertExists($fileNew);

        $handler = new StoragePruneHandler($this->config);

        // Delete all files before 2023-01-01
        $deletedCount = $handler->pruneBeforeDate($project, $pruneDate);

        $this->assertEquals(1, $deletedCount, 'Exactly one file should have been deleted.');
        Storage::disk('local')->assertMissing($fileOld);
        Storage::disk('local')->assertExists($fileNew);
    }

    /**
     * Tests that the `pruneBeforeDate` method correctly ignores files with invalid filenames
     * that do not match the expected date format or naming convention.
     *
     * @return void
     */
    public function test_prune_before_date_ignores_invalid_filenames(): void
    {
        $project = 'test-project';
        $pruneDate = '2023-01-01';
        $basePath = "uplinkr/{$project}/probes";

        Storage::fake('local');

        // File without a date separator or wrong format
        $invalidFile = "{$basePath}/invalid-filename.log";
        Storage::disk('local')->put($invalidFile, 'content');

        $handler = new StoragePruneHandler($this->config);

        $deletedCount = $handler->pruneBeforeDate($project, $pruneDate);

        $this->assertEquals(0, $deletedCount);
        Storage::disk('local')->assertExists($invalidFile);
    }

    /**
     * Tests that the pruneBeforeDate method throws an exception when an invalid date argument is provided.
     * This ensures that the method validates the date format correctly and handles invalid inputs
     * by throwing an InvalidArgumentException with an appropriate message.
     *
     * @return void
     */
    public function test_prune_before_date_throws_exception_on_invalid_date_argument(): void
    {
        $handler = new StoragePruneHandler($this->config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');

        $handler->pruneBeforeDate('project', 'no-date');
    }

    /**
     * Tests the functionality of deleting a directory within a storage disk.
     * This method verifies that the specified directory is successfully removed
     * from the storage after invoking the deleteDirectory method of the storage handler.
     *
     * @return void
     */
    public function test_delete_directory(): void
    {
        Storage::fake('local');
        Storage::disk('local')->makeDirectory('folder-to-delete');
        Storage::disk('local')->assertExists('folder-to-delete');

        $handler = new StoragePruneHandler($this->config);
        $handler->deleteDirectory('folder-to-delete');

        Storage::disk('local')->assertMissing('folder-to-delete');
    }

    /**
     * Tests the creation of a new directory using the makeDirectory method in StoragePruneHandler.
     * This method ensures that the specified directory is properly created in the configured storage disk.
     *
     * @return void
     */
    public function test_make_directory(): void
    {
        Storage::fake('local');

        $handler = new StoragePruneHandler($this->config);
        $handler->makeDirectory('new-folder');

        Storage::disk('local')->assertExists('new-folder');
    }
}