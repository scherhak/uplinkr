<?php

namespace Uplinkr\Objects\Config;

/**
 * Configuration value object for Uplinkr application settings.
 *
 * Provides type-safe access to configuration values with centralized defaults.
 *
 * @package Uplinkr\Objects\Config
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <uplinkr@scherhak.com>
 */
final class UplinkrConfig
{
    public function __construct(
        public string $storagePath = 'uplinkr',
        public string $probeResultsPath = 'results',
        public string $standardProject = 'standard_project',
        public string $probeFilenameSeparator = '@',
        public string $fileExtension = 'log'
    )
    {
    }

    /**
     * Creates an instance from Laravel's config repository.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        return new self(
            storagePath: config('uplinkr.storage.path', 'uplinkr'),
            probeResultsPath: config('uplinkr.storage.probe_results', 'probes'),
            standardProject: config('uplinkr.storage.standard_project', 'standard_project'),
            probeFilenameSeparator: config('uplinkr.storage.probe_filename_separator', '@'),
            fileExtension: config('uplinkr.storage.file_extension', 'log')
        );
    }

    /**
     * Get the storage path.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * @return string
     */
    public function getProbeResultsPath(): string
    {
        return $this->probeResultsPath;
    }

    /**
     * Get the standard project name.
     *
     * @return string
     */
    public function getStandardProject(): string
    {
        return $this->standardProject;
    }

    /**
     * Get the file extension for storage files.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * @return string
     */
    public function getProbeFilenameSeparator(): string
    {
         return $this->probeFilenameSeparator;
    }
}