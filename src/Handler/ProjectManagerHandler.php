<?php

namespace Uplinkr\Handler;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;

class ProjectManagerHandler
{
    /**
     * @var Filesystem $storage
     */
    private Filesystem $storage;

    /**
     * @param UplinkrConfig $config
     */
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
        $this->storage = Storage::disk($this->config->getStorageDisc());
    }

    /**
     * @param string $projectName
     * @return bool
     */
    public function exists(string $projectName): bool
    {
        return $this->storage->exists(sprintf('%s/%s', $this->config->getStoragePath(), $projectName));
    }

    /**
     * @param string $projectName
     * @return bool
     */
    public function archive(string $projectName): bool
    {
        return File::copyDirectory(
            $this->getProjectPath(projectName: $projectName),
            $this->setArchivePath(projectName: $projectName)
        );
    }

    /**
     * @param string $projectName
     * @return void
     */
    public function delete(string $projectName): void
    {
        Storage::disk($this->config->getStorageDisc())->deleteDirectory($projectName);
    }

    /**
     * @return array
     */
    public function listAll(): array
    {
        return Storage::disk($this->config->getStorageDisc())->directories($this->config->getStoragePath());
    }

    /**
     * @param string $path
     * @return int
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
     * @param string $projectName
     * @return string
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
     * @param string $projectName
     * @return string
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