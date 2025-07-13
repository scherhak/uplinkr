<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Facades\Log;

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
//        DB::table('uplinkr_logs')->insert($data);
        Log::info(__METHOD__, $data);
    }
}
