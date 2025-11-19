<?php

namespace Uplinkr\Objects\Config;

/**
 * Configuration value object for Uplinkr application settings.
 *
 * Provides type-safe access to configuration values with centralized defaults.
 *
 * @package Uplinkr\Objects\Config
 * @version 1
 * @copyright 2025-today Sascha Scherhak / uplinkr.dev
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
final class UplinkrConfig
{
    public function __construct(
        public string $storageDisk = 'local',
        public string $storagePath = 'uplinkr',
        public string $standardProject = 'standard_project',
        public string $probeResultsPath = 'probes',
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
            storageDisk: config('uplinkr.storage.disk', 'local'),
            storagePath: config('uplinkr.storage.path', 'uplinkr'),
            standardProject: config('uplinkr.storage.standard_project', 'standard_project'),
            probeResultsPath: config('uplinkr.storage.probe_results', 'probes'),
            probeFilenameSeparator: config('uplinkr.storage.probe_filename_separator', '@'),
            fileExtension: config('uplinkr.storage.file_extension', 'log')
        );
    }

    /**
     * @return string
     */
    public function getStorageDisc(): string
    {
        return $this->storageDisk;
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