<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Facades\Storage;
use JsonException;

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
        $filename = 'uplinkr/' . date('Y-m-d') . '.log';
        Storage::disk('local')->append($filename, json_encode($data, JSON_THROW_ON_ERROR));
    }
}
