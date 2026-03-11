<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Support\Sanitizer;

/**
 * Class FileProbeResultsStorage
 * @package Uplinkr\Storage
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class FileProbeResultsStorage implements ProbeResultsStorageInterface
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
     * Saves a probe result to storage.
     *
     * @param array $resultData The probe result data to save
     * @return void
     * @throws JsonException
     */
    public function saveResult(array $resultData): void
    {
        $filename = $this->buildFilename($resultData);
        $disk = Storage::disk($this->config->getStorageDisc());

        $existingData = [];
        if ($disk->exists($filename)) {
            $content = $disk->get($filename);
            if (!empty($content)) {
                $existingData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            }
        }

        $existingData[] = $resultData;
        $flags = JSON_THROW_ON_ERROR;

        if ($this->config->shouldPrettyPrintProbeResults()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $disk->put(
            $filename,
            json_encode($existingData, $flags)
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
            $this->getProjectPath(data: $data),
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
        $projectValues = new ProjectValues(data: Arr::get($data, 'settings', []));

        $project = $projectValues->getName();

        if ($project === 'unknown') {
            $project = $this->config->getStandardProject();
        }

        return $project;
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

        // fallback to the default filename name if no URL is found.
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

        return null;
    }

    /**
     * Retrieves the current date in the configured grouping format.
     *
     * @return string The current date formatted according to probe_results_grouping config.
     */
    private function getCurrentDate(): string
    {
        return date($this->config->getProbeResultsDateFormat());
    }
}
