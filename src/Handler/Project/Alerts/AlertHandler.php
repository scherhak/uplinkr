<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Support\Arr;
use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Support\Time;

/**
 * Class AlertHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
readonly class AlertHandler
{
    /**
     * Constructor method for initializing the class with a project storage instance.
     *
     * @param ProjectStorageInterface $projectStorage The project storage implementation.
     * @return void
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage
    )
    {
    }

    /**
     * Updates the alert settings for an existing project.
     *
     * @param array $options An associative array containing project details and alert settings.
     * @return bool
     * @throws JsonException
     */
    public function handle(array $options): bool
    {
        $projectName = Arr::get($options, 'project');
        if (!$projectName || $projectName === 'unknown') {
            return false;
        }

        $projectData = $this->projectStorage->findProject($projectName);
        if (!$projectData) {
            return false;
        }

        // Load existing alert settings or use defaults
        $existingAlerts = Arr::get($projectData, 'alerts.0', []);

        $alert = [
            'enabled' => Arr::get($options, 'enabled') ?? Arr::get($existingAlerts, 'enabled', true),
            'trigger_after_failures' => Arr::get($options, 'trigger_after_failures') ?? Arr::get($existingAlerts, 'trigger_after_failures', 3),
            'cooldown_minutes' => Arr::get($options, 'cooldown_minutes') ?? Arr::get($existingAlerts, 'cooldown_minutes', 30),
            'latency_threshold_ms' => Arr::get($options, 'latency_threshold_ms') ?? Arr::get($existingAlerts, 'latency_threshold_ms', 1500),
            'trigger_after_slow' => Arr::get($options, 'trigger_after_slow') ?? Arr::get($existingAlerts, 'trigger_after_slow', 3),
            'channels' => Arr::get($options, 'channels') ?? Arr::get($existingAlerts, 'channels', ['mail']),
        ];

        // Currently, the requirement says "alerts": [] and shows a list with one object.
        // I will replace/set the first alert or handle it as a single alert configuration for now,
        // as the issue description shows an array with one element.
        Arr::set($projectData, 'alerts', [$alert]);
        Arr::set($projectData, 'updated_at', Time::now());

        $this->projectStorage->saveProject($projectData);

        return true;
    }
}
