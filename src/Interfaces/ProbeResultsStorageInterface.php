<?php

namespace Uplinkr\Interfaces;

/**
 * Interface defining a contract for storage handling.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
interface ProbeResultsStorageInterface
{
    /**
     * Saves the provided result data.
     *
     * @param array $resultData
     * @return void
     */
    public function saveResult(array $resultData): void;
}
