<?php

namespace Uplinkr\Storage;

/**
 * Interface defining a contract for storage handling.
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 */
interface StorageInterface
{
    public function saveResult(array $data): void;
}
