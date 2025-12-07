<?php

namespace Uplinkr\Handler;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ProjectManagerHandler
 * @package Uplinkr\Handler
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProjectManagerHandler
{
    /**
     * @var Filesystem $storage
     */
    private Filesystem $storage;

    /**
     * Constructor method.
     *
     * @param UplinkrConfig $config The configuration object used to initialize the storage.
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
        $this->storage = Storage::disk($this->config->getStorageDisc());
    }

    /**
     * Checks whether the specified project exists in the storage.
     *
     * @param string $projectName Name of the project to check.
     * @return bool Returns true if the project exists, false otherwise.
     */
    public function exists(string $projectName): bool
    {
        return $this->storage->exists(sprintf('%s/%s', $this->config->getStoragePath(), $projectName));
    }

    /**
     * Archives the specified project by copying its directory to an archive location.
     *
     * @param string $projectName The name of the project to archive.
     * @return bool Returns true if the project directory was successfully copied to the archive, false otherwise.
     */
    public function archive(string $projectName): bool
    {
        return File::copyDirectory(
            $this->getProjectPath(projectName: $projectName),
            $this->setArchivePath(projectName: $projectName)
        );
    }

    /**
     * Deletes a directory corresponding to the specified project name.
     *
     * @param string $projectName The name of the project whose directory is to be deleted.
     * @return bool True if the directory was successfully deleted, false otherwise.
     */
    public function delete(string $projectName): bool
    {
        return Storage::disk($this->config->getStorageDisc())->deleteDirectory($projectName);
    }

    /**
     * Retrieves a list of all directories within the specified storage path.
     *
     * @return array An array of directory paths present in the configured storage disk and path.
     */
    public function listAll(): array
    {
        return Storage::disk($this->config->getStorageDisc())->directories($this->config->getStoragePath());
    }

    /**
     * Retrieves the count of probe files stored in the specified path.
     *
     * @param string $path The base directory path where probe files are located.
     * @return int The total number of probe files found in the specified directory.
     */
    public function getProbesCount(string $path): int
    {
        return count(Storage::disk($this->config->getStorageDisc())->allFiles(
            sprintf('%s/%s',
                $path,
                $this->config->getProbeResultsPath()
            )));
    }

    /**
     * Retrieves the full storage path for a specified project.
     *
     * @param string $projectName The name of the project for which to get the path.
     * @return string The full path to the specified project's storage location.
     */
    private function getProjectPath(string $projectName): string
    {
        return $this->storage->path(
            sprintf(
                '%s/%s',
                $this->config->getStoragePath(),
                $projectName)
        );
    }

    /**
     * Sets the archive path for a given project name.
     *
     * @param string $projectName The name of the project for which the archive path is being set.
     * @return string The full path to the archived project.
     */
    private function setArchivePath(string $projectName): string
    {
        return $this->storage->path(
            sprintf(
                '%s/%s/%s',
                $this->config->getStoragePath(),
                $this->config->getArchivedFolder(),
                $projectName)
        );
    }
}