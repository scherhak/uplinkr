<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class FileStorage
 * @package Uplinkr\Storage
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <uplinkr@scherhak.com>
 */
class FileStorage implements StorageInterface
{
    /**
     * Constructor method for initializing the class with configuration.
     *
     * @param UplinkrConfig $config The configuration object required for the class.
     *
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function saveResult(array $resultData): void
    {
        Log::debug('Uplinkr_fileStorage', [
            'data' => $resultData,
            'filename' => $this->buildFilename($resultData),
        ]);

        Storage::disk('local')->append(
            $this->buildFilename($resultData),
            json_encode($resultData, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Constructs a filename based on the provided data and application configuration.
     *
     * @param array $data An associative array containing metadata, including 'settings.uri', used for building the filename.
     * @return string The generated filename as a string.
     */
    private function buildFilename(array $data): string
    {
        return sprintf('%s/%s/%s-%s.%s',
            $this->buildStoragePath(),
            $this->getProjectPath(data: $data),
            $this->getUrlFromData(data: $data),
            $this->getCurrentDate(),
            $this->getFileExtension(),
        );
    }

    /**
     * Constructs the full storage path by combining the base storage path and the probes results path.
     *
     * @return string The constructed storage path.
     */
    private function buildStoragePath(): string
    {
        return sprintf('%s/%s', $this->getStoragePath(), $this->getProbesResultsPath());
    }

    /**
     * Retrieves the storage path for the application.
     *
     * @return string The configured storage path or the default path if not set.
     */
    private function getStoragePath(): string
    {
        return $this->config->getStoragePath();
    }

    /**
     * Retrieves the storage path used for storing results.
     *
     * @return string The path to the result storage directory.
     */
    private function getProbesResultsPath(): string
    {
        return $this->config->getProbeResultsPath();
    }

    /**
     * Retrieves the project path from the configuration settings.
     *
     * @return string The configured project path or a default value if not set.
     */
    private function getProjectPath(array $data): string
    {
        return Arr::get(
            $data,
            'settings.project',
            $this->config->getStandardProject()
        );
    }

    /**
     * Retrieves the file extension for storage file configuration.
     *
     * @return string The configured file extension, or 'log' as the default value.
     */
    private function getFileExtension(): string
    {
        return $this->config->getFileExtension();
    }

    /**
     * Extracts the URI from the provided data array.
     *
     * @param array $data The input data array containing settings information.
     * @return string The URI retrieved from the data array.
     */
    private function getUrlFromData(array $data): string
    {
        return Arr::get($data, 'settings.url');
    }

    /**
     * Retrieves the current date in the format 'YYYY-MM-DD'.
     *
     * @return string The current date formatted as 'YYYY-MM-DD'.
     */
    private function getCurrentDate(): string
    {
        return date('Y-m-d');
    }
}
