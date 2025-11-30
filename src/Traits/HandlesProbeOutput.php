<?php

namespace Uplinkr\Traits;

use Illuminate\Support\Arr;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Trait HandlesProbeOutput
 * @package Uplinkr\Traits
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
trait HandlesProbeOutput
{
    /**
     * Generates and displays result messages based on the probe status and stores API messages associated with a project.
     *
     * @param array $result The result array containing probe status and related information.
     * @param string|null $project The specific project name, or null to use the default project from the configuration.
     * @param UplinkrConfig $config The configuration object providing access to settings such as the default project.
     * @param string $probeType The type of probe used in the operation (e.g., HTTP, Ping).
     * @return void
     */
    protected function resultMessages(array $result, string|null $project, UplinkrConfig $config, string $probeType): void
    {
        if (Arr::get($result, 'probe_status') === 'reachable') {
            $this->info(__(sprintf('uplinkr::messages.%s_reachable', $probeType), [
                'time_in_ms' => Arr::get($result, 'probe_message.duration_ms'),
            ]));
        } else {
            $this->error(__(sprintf('uplinkr::messages.%s_unreachable', $probeType), [
                'status_header' => Arr::get($result, 'status_header'),
                'time_in_ms' => Arr::get($result, 'probe_message.duration_ms'),
            ]));
        }

        $defaultProject = $config->getStandardProject();
        $this->info(__('uplinkr::messages.api_stored', ['project' => $project ?? $defaultProject]));
    }
}