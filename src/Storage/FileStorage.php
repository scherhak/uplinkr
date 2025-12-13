<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

/**
 * Class FileStorage
 * @package Uplinkr\Storage
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
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
        private readonly UplinkrConfig $config,
        private readonly Sanitizer     $sanitizer,
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function saveResult(array $resultData): void
    {
        Storage::disk($this->config->getStorageDisc())->append(
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
        return sprintf('%s/%s%s%s.%s',
            $this->buildStoragePath(data: $data),
            $this->getUrlFromData(data: $data),
            $this->getFilenameSeparator(),
            $this->getCurrentDate(),
            $this->getFileExtension(),
        );
    }

    /**
     * Constructs the full storage path by combining the base storage path and the probes results path.
     *
     * @return string The constructed storage path.
     */
    private function buildStoragePath(array $data): string
    {
        return sprintf('%s/%s/%s',
            $this->getStoragePath(),
            $this->getProjectPath($data),
            $this->getProbesResultsPath()
        );
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

    /**w
     * Generates a sanitized URL from the given data array.
     *
     * Processes the input to ensure a valid URL structure,
     * extracts the host, sanitizes the host name, and formats it
     * into a lowercase string for consistency.
     *
     * @param array $data The input data to extract and process the URL from.
     * @return string The sanitized, formatted version of the URL.
     */
    private function getUrlFromData(array $data): string
    {
        // check the target url
        $rawUrl = $this->findTargetUrl($data);

        // TODO (0.1.0) Add a fallback to the default filename name if no URL is found.
        if ($rawUrl === null) {
            return $this->config->getStandardProject();
        }

        return $this->sanitizer->url($rawUrl);
    }

    /**
     * Retrieves the separator used in filenames.
     *
     * @return string The character or string used as a filename separator.
     */
    private function getFilenameSeparator(): string
    {
        return $this->config->getProbeFilenameSeparator();
    }

    /**
     * Attempts to extract the target URL or API endpoint from the settings.
     *
     * @param array $data
     * @return string|null Returns the URL string if found, otherwise null.
     */
    private function findTargetUrl(array $data): ?string
    {
        if (Arr::has($data, 'settings.url')) {
            return (string)Arr::get($data, 'settings.url');
        }

        // TODO (0.1.0) Remove this fallback once the API endpoint is mandatory.
        if (Arr::has($data, 'settings.endpoint')) {
            return (string)Arr::get($data, 'settings.endpoint');
        }

        return null;
    }

    /**
     * Retrieves the current date in the format 'YYYY-MM-DD'.
     *
     * @return string The current date formatted as 'YYYY-MM-DD'.
     *
     * TODO (0.2.0) Embed this part into the configuration and see what sensible combinations are possible.
     */
    private function getCurrentDate(): string
    {
        return date('Y-m-d');
    }
}
