<?php

namespace Uplinkr\Handler\Project;

use Arr;
use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Support\Time;

/**
 * Class AlertHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AlertHandler
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

        $alert = [
            'enabled' => (bool)Arr::get($options, 'enabled', true),
            'trigger_after_failures' => (int)Arr::get($options, 'trigger_after_failures', 3),
            'cooldown_minutes' => (int)Arr::get($options, 'cooldown_minutes', 30),
            'latency_threshold_ms' => (int)Arr::get($options, 'latency_threshold_ms', 1500),
            'trigger_after_slow' => (int)Arr::get($options, 'trigger_after_slow', 3),
            'channels' => (array)Arr::get($options, 'channels', ['mail']),
        ];

        // Currently, the requirement says "alerts": [] and shows a list with one object.
        // I will replace/set the first alert or handle it as a single alert configuration for now,
        // as the issue description shows an array with one element.
        $projectData['alerts'] = [$alert];
        $projectData['updated_at'] = Time::now();

        $this->projectStorage->saveProject($projectData);

        return true;
    }
}
