<?php

namespace Uplinkr\Handler\Project;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ListHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ListHandler
{
    /**
     * Constructor method.
     *
     * @param UplinkrConfig $config Configuration instance.
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {}

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
}