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
    public function saveResult(array $data): void
    {
        Storage::disk('local')->append(
            $this->buildFilename($data),
            json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Constructs a filename based on the provided data.
     *
     * @param array $data An associative array containing data required to build the filename.
     *                    The 'settings.uri' key is expected to derive part of the filename.
     * @return string The constructed filename in the format 'uplinkr/{uri}-{date}.log'.
     */
    private function buildFilename(array $data): string
    {
        return sprintf('%s/%s-%s.%s',
            config('uplinkr.storage.path', 'uplinkr'),
            Arr::get($data, 'settings.uri'),
            date('Y-m-d'),
            config('uplinkr.storage.file_extension', 'log'),
        );
    }
}
