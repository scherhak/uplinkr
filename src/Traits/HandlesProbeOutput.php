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
     * @return void
     */
    protected function resultMessages(array $result, string|null $project, UplinkrConfig $config): void
    {
        if (Arr::get($result, 'probe_status') === 'reachable') {
            $this->info(__('uplinkr::messages.probe_reachable', [
                'time_in_ms' => Arr::get($result, 'probe_message.duration_ms'),
            ]));
        } else {
            $this->error(__('uplinkr::messages.probe_unreachable', [
                'status_header' => Arr::get($result, 'status_header'),
                'time_in_ms' => Arr::get($result, 'probe_message.duration_ms'),
            ]));
        }

        $defaultProject = $config->getStandardProject();
        $this->info(__('uplinkr::messages.probe_stored', ['project' => $project ?? $defaultProject]));
    }
}