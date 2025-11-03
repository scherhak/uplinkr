<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Facades\Log;
use Uplinkr\Interfaces\StorageInterface;

/**
 * Class DatabaseStorage
 * @package Uplinkr\Storage
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 */
class DatabaseStorage implements StorageInterface
{
    public function saveResult(array $data): void
    {
        // [-WIP-]
    }
}
