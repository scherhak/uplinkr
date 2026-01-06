<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

/**
 * Class AlertDecisionHandler
 * @package Uplinkr\Handler\Project\Alerts
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AlertDecisionHandler
{
    /**
     * @param ProjectStorageInterface $projectStorage
     * @param UplinkrConfig $config
     * @param Sanitizer $sanitizer
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage,
        private readonly UplinkrConfig           $config,
        private readonly Sanitizer               $sanitizer
    )
    {
    }

    /**
     * Decides whether an alert should be triggered for a project.
     *
     * @param string $projectName
     * @return array
     * @throws JsonException
     */
    public function handle(string $projectName): array
    {
        $projectSettings = $this->projectStorage->findProject($projectName);
        if (!$projectSettings) {
            return [];
        }

        $alerts = Arr::get($projectSettings, 'alerts', []);
        $state = $this->loadState($projectName);

        if (empty($state)) {
            return [];
        }

        $decisions = [];

        foreach ($alerts as $alert) {
            if (Arr::get($alert, 'enabled') !== true) {
                continue;
            }

            foreach (Arr::get($state, 'probes', []) as $probeKey => $probeData) {
                $consecutiveFailures = Arr::get($probeData, 'consecutive_failures', 0);
                $triggerAfterFailures = Arr::get($alert, 'trigger_after_failures', 3);

                if ($consecutiveFailures >= $triggerAfterFailures) {
                    $decisions[] = [
                        'project' => $projectName,
                        'probe' => $probeKey,
                        'alert' => $alert,
                        'reason' => 'consecutive_failures',
                        'count' => $consecutiveFailures
                    ];
                }
            }
        }

        return $decisions;
    }

    /**
     * Loads the state.json for the project.
     *
     * @param string $projectName
     * @return array
     * @throws JsonException
     */
    private function loadState(string $projectName): array
    {
        $projectDir = sprintf('%s/%s', $this->config->getStoragePath(), $this->sanitizeProjectName($projectName));
        $stateFile = sprintf('%s/state.%s', $projectDir, $this->config->getFileExtension());
        $disk = Storage::disk($this->config->getStorageDisc());

        if (!$disk->exists($stateFile)) {
            return [];
        }

        $content = $disk->get($stateFile);
        if (empty($content)) {
            return [];
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Sanitizes a given project name.
     *
     * @param string $project
     * @return string
     */
    private function sanitizeProjectName(string $project): string
    {
        return $this->sanitizer->project($project);
    }
}
