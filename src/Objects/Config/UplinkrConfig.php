<?php

namespace Uplinkr\Objects\Config;

/**
 * @package Uplinkr\Objects\Config
 *
 * Configuration value object for Uplinkr application settings.
 * Provides type-safe access to configuration values with centralized defaults.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
final class UplinkrConfig
{
    public function __construct(
        public string $storageDisk = 'local',
        public string $storagePath = 'uplinkr',
        public string $probeResultsPath = 'probes',
        public int    $standardLatency = 1500,
        public string $probeFilenameSeparator = '@',
        public string $fileExtension = 'json',
        public string $archivedFolder = 'archived',
        public bool   $allowCompleteWipe = false,
        public string $standardProject = 'standard_project',
        public string $standardProjectStatus = 'enabled',
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
            probeResultsPath: config('uplinkr.storage.probe_results', 'probes'),
            standardLatency: config('uplinkr.probes.standard_latency', 1500),
            probeFilenameSeparator: config('uplinkr.storage.probe_filename_separator', '@'),
            fileExtension: config('uplinkr.storage.file_extension', 'json'),
            archivedFolder: config('uplinkr.storage.archive_folder', 'archived'),
            allowCompleteWipe: config('uplinkr.storage.allow_complete_wipe', false),
            standardProject: config('uplinkr.projects.standard_project', 'standard_project'),
            standardProjectStatus: config('uplinkr.projects.standard_project_status', 'enabled'),
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
     * @return string
     */
    public function getStandardProjectStatus(): string
    {
        return $this->standardProjectStatus;
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

    /**
     * @return string
     */
    public function getArchivedFolder(): string
    {
        return $this->archivedFolder;
    }

    /**
     * @return bool
     */
    public function allowCompleteWipe(): bool
    {
        return $this->allowCompleteWipe;
    }
}