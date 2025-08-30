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

    private function buildFilename(array $data): string
    {
        return sprintf('uplinkr/%s-%s.log',
            Arr::get($data, 'settings.uri'),
            date('Y-m-d')
        );
    }
}
