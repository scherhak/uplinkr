<?php

namespace Uplinkr\Tests\Handler;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Uplinkr\Handler\StoragePruneHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class StoragePruneHandlerTest extends TestCase
{
    private StoragePruneHandler $handler;

    /**
     * Initializes the test setup by creating an instance of the configuration file
     * and setting up the StoragePruneHandler with the provided configuration.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // We use a real instance of the config file because it's a data object.
        // This best reflects the app's behavior.
        $config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            standardProject: 'test_project',
            probeResultsPath: 'probes',
            probeFilenameSeparator: '@',
            fileExtension: 'log'
        );

        $this->handler = new StoragePruneHandler($config);
    }

    /**
     * Tests whether files with a modification date older than the given prune date are deleted
     * while files with a modification date on or after the prune date are retained.
     *
     * @return void
     */
    public function test_prune_before_date_deletes_old_files(): void
    {
        Storage::fake('local');

        $project = 'test_project';
        $pruneDate = '2023-06-01'; // Everything before this date will be deleted

        // Simulate the path: uplinkr/test_project/probes/
        $basePath = "uplinkr/{$project}/probes";

        // File 1: Old (should be deleted) - 2023-01-01
        $oldFile = "{$basePath}/old-id@2023-01-01.log";
        // File 2: New (should remain) - 2024-01-01
        $newFile = "{$basePath}/new-id@2024-01-01.log";

        Storage::disk('local')->put($oldFile, 'dummy content');
        Storage::disk('local')->put($newFile, 'dummy content');

        $deletedCount = $this->handler->pruneBeforeDate($project, $pruneDate);

        $this->assertEquals(1, $deletedCount, 'Exactly one file should have been deleted.');
        Storage::disk('local')->assertMissing($oldFile);
        Storage::disk('local')->assertExists($newFile);
    }

    /**
     * Tests that the prune process ignores files with invalid filenames,
     * such as those missing a required separator or containing invalid date segments.
     *
     * @return void
     */
    public function test_prune_ignores_invalid_filenames(): void
    {
        Storage::fake('local');
        $project = 'test_project';
        $basePath = "uplinkr/{$project}/probes";

        // File without separator and date
        $invalidFile = "{$basePath}/invalid-filename.log";
        Storage::disk('local')->put($invalidFile, 'content');

        // File with invalid date part
        $invalidDateFile = "{$basePath}/id@not-a-date.log";
        Storage::disk('local')->put($invalidDateFile, 'content');

        // We attempt to delete (date does not matter because filenames do not match)
        $deletedCount = $this->handler->pruneBeforeDate($project, '2099-01-01');

        $this->assertEquals(0, $deletedCount);
        Storage::disk('local')->assertExists($invalidFile);
        Storage::disk('local')->assertExists($invalidDateFile);
    }

    /**
     * Tests the prune functionality to verify that it returns zero when the specified directory does not exist.
     *
     * @return void
     */
    public function test_prune_returns_zero_if_directory_does_not_exist(): void
    {
        Storage::fake('local');
        $deletedCount = $this->handler->pruneBeforeDate('non_existent_project', '2023-01-01');
        $this->assertEquals(0, $deletedCount);
    }

    /**
     * Tests if the pruneBeforeDate method throws an exception when provided with an invalid date format.
     * Verifies that an InvalidArgumentException is thrown with the expected message.
     *
     * @return void
     */
    public function test_prune_throws_exception_on_invalid_date_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');

        $this->handler->pruneBeforeDate('test_project', 'invalid-date-string');
    }

    /**
     * Tests the functionality of creating a directory using the handler
     * and verifies that the directory exists in the storage disk.
     *
     * @return void
     */
    public function test_make_directory(): void
    {
        Storage::fake('local');

        $dir = 'new-directory';
        $this->handler->makeDirectory($dir);

        Storage::disk('local')->assertExists($dir);
    }

    /**
     * Tests the functionality of deleting a directory using the deleteDirectory method.
     * Ensures the directory is created, exists, and is properly deleted afterward.
     *
     * @return void
     */
    public function test_delete_directory(): void
    {
        Storage::fake('local');
        $dir = 'dir-to-delete';
        
        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->assertExists($dir);

        $this->handler->deleteDirectory($dir);

        Storage::disk('local')->assertMissing($dir);
    }
}