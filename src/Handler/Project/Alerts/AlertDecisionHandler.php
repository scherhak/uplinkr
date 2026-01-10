<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Support\Time;

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
     * @param ListHandler $listHandler
     * @param UplinkrConfig $config
     * @param Sanitizer $sanitizer
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage,
        private readonly ListHandler             $listHandler,
        private readonly UplinkrConfig           $config,
        private readonly Sanitizer               $sanitizer
    )
    {
    }

    /**
     * Decides whether an alert should be triggered for a project.
     *
     * @param string|null $projectName
     * @return array
     * @throws JsonException
     */
    public function handle(?string $projectName = null): array
    {
        if ($projectName === null) {
            $allDecisions = [];
            $projects = $this->listHandler->all();

            foreach ($projects as $projectPath) {
                $name = basename($projectPath);
                $allDecisions[] = $this->handle($name);
            }

            if (empty($allDecisions)) {
                return [];
            }

            return array_merge(...$allDecisions);
        }

        $projectSettings = $this->projectStorage->findProject($projectName);
        if (!$projectSettings) {
            return [];
        }

        $alerts = Arr::get($projectSettings, 'alarms', Arr::get($projectSettings, 'alerts', []));
        $state = $this->loadState($projectName);

        if (empty($state)) {
            return [];
        }

        $decisions = [];
        $stateUpdated = false;

        foreach ($alerts as $alert) {
            if (Arr::get($alert, 'enabled') !== true) {
                continue;
            }

            foreach (Arr::get($state, 'probes', []) as $probeKey => $probeData) {

                $consecutiveFailures = Arr::get($probeData, 'consecutive_failures', 0);
                $triggerAfterFailures = Arr::get($alert, 'trigger_after_failures', 3);

                if ($consecutiveFailures >= $triggerAfterFailures) {

                    // TODO Replace this with first notification
                    Log::warning(sprintf(
                        'Alert triggered for project "%s" on probe "%s". Reason: %s (%d failures)',
                        $projectName,
                        $probeKey,
                        'consecutive_failures',
                        $consecutiveFailures
                    ));

                    $decisions[] = [
                        'project' => $projectName,
                        'probe' => $probeKey,
                        'alert' => $alert,
                        'reason' => 'consecutive_failures',
                        'count' => $consecutiveFailures
                    ];

                    // Update total_failures in state
                    $totalFailures = Arr::get($probeData, 'total_failures');
                    if ($totalFailures === null) {
                        $totalFailures = $consecutiveFailures;
                    } else {
                        $totalFailures += $triggerAfterFailures;
                    }
                    
                    $state['probes'][$probeKey]['total_failures'] = $totalFailures;
                    $state['probes'][$probeKey]['consecutive_failures'] = 0;
                    $state['probes'][$probeKey]['last_notified_failure_at'] = Time::now();
                    $stateUpdated = true;
                }
            }
        }

        if ($stateUpdated) {
            $this->saveState($projectName, $state);
        }

        return $decisions;
    }

    /**
     * Saves the state.json for the project.
     *
     * @param string $projectName
     * @param array $state
     * @return void
     * @throws JsonException
     */
    private function saveState(string $projectName, array $state): void
    {
        $projectDir = sprintf('%s/%s', $this->config->getStoragePath(), $this->sanitizeProjectName($projectName));
        $stateFile = sprintf('%s/state.%s', $projectDir, $this->config->getFileExtension());
        $disk = Storage::disk($this->config->getStorageDisc());

        $disk->put($stateFile, json_encode($state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
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
