<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Interfaces\StorageInterface;

/**
 * Class FileStorage
 * @package Uplinkr\Storage
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 */
class FileStorage implements StorageInterface
{
    /**
     * @throws JsonException
     */
    public function saveResult(array $resultData): void
    {
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
            config('uplinkr.storage.path', 'uplinkr'),
            config('uplinkr.storage.standard_project', 'standard_project'),
            Arr::get($data, 'settings.uri'),
            date('Y-m-d'),
            config('uplinkr.storage.file_extension', 'log'),
        );
    }
}
