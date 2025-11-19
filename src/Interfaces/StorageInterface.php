<?php

namespace Uplinkr\Interfaces;

/**
 * Interface defining a contract for storage handling.
 *
 * @version 1
 * @copyright 2025-today Sascha Scherhak / uplinkr.dev
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
interface StorageInterface
{
    /**
     * Saves the provided result data.
     *
     * @param array $resultData
     * @return void
     */
    public function saveResult(array $resultData): void;
}
