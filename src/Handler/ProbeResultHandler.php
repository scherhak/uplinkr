<?php

namespace Uplinkr\Handler;

use Illuminate\Support\Arr;

/**
 * Class ProbeResultHandler
 * @package Uplinkr\Handler
 *
 * @version 1
 * @copyright 2025-today Sascha Scherhak / uplinkr.dev
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeResultHandler
{
    /**
     * @param array $result
     */
    public function __construct(
        private readonly array $result
    )
    {
    }

    /**
     * Builds the complete probe result with all metadata.
     *
     * @param float $durationTime The time taken to perform the probe in seconds
     * @param array $probeMessage The probe message array containing message and lang_key
     * @param string $status The status of the probe (reachable, unreachable, not-reachable)
     * @param array $settings The settings used for the probe (protocol, uri)
     * @return array The complete result array with all metadata
     */
    public function build(float $durationTime, array $probeMessage, string $status, array $settings): array
    {
        $probeMessage = Arr::add($probeMessage, 'duration_ms', round($durationTime * 1000, 2));
        $probeMessage = Arr::add($probeMessage, 'duration_s', round($durationTime, 2));

        return array_merge($this->result, [
            'time_to_load' => $durationTime,
            'probe_message' => $probeMessage,
            'status' => $status,
            'executed' => now(),
            'settings' => $settings,
        ]);
    }
}