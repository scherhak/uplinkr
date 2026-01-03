<?php

namespace Uplinkr\Handler\Probe;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

/**
 * Class ResultHandler
 * @package Uplinkr\Handler
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ResultHandler
{
    /**
     * @var array $result
     */
    private array $result = [];

    /**
     * @param UplinkrConfig $config
     * @param Sanitizer $sanitizer
     */
    public function __construct(
        private readonly UplinkrConfig $config,
        private readonly Sanitizer     $sanitizer
    )
    {
    }

    /**
     * @param array $result
     * @return $this
     */
    public function with(array $result): self
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Builds the complete probe result with all metadata.
     *
     * @param float $durationTime The time taken to perform the probe in seconds
     * @param array $probeMessage The probe message array containing message and lang_key
     * @param string $probeStatus The status of the probe (reachable, unreachable, not-reachable)
     * @param array $settings The settings used for the probe (protocol, uri)
     * @return array The complete result array with all metadata
     */
    public function build(float $durationTime, array $probeMessage, string $probeStatus, array $settings): array
    {
        $probeId = Str::uuid()->toString();
        $probeMessage = Arr::add($probeMessage, 'duration_ms', round($durationTime * 1000, 2));
        $probeMessage = Arr::add($probeMessage, 'duration_s', round($durationTime, 2));

        $result = array_merge($this->result, [
            'probe_id' => $probeId,
            'probe_message' => $probeMessage,
            'probe_status' => $probeStatus,
            'time_to_load' => $durationTime,
            'executed' => now(),
            'settings' => $settings,
        ]);

        if ($probeStatus !== 'reachable') {
            $this->updateState($result);
        }

        return $result;
    }

    /**
     * Updates the state.json for the project.
     *
     * @param array $result The current probe result.
     * @return void
     */
    private function updateState(array $result): void
    {
        $project = Arr::get($result, 'settings.project');
        $url = Arr::get($result, 'settings.url');
        $method = Arr::get($result, 'settings.method', 'GET');

        if (!$project || !$url) {
            return;
        }

        $projectDir = sprintf('%s/%s', $this->config->getStoragePath(), $this->sanitizeProjectName($project));
        $stateFile = sprintf('%s/state.%s', $projectDir, $this->config->getFileExtension());
        $disk = Storage::disk($this->config->getStorageDisc());

        $state = [
            'project' => $project,
            'updated_at' => now()->toDateTimeString(),
            'probes' => [],
        ];

        if ($disk->exists($stateFile)) {
            $content = $disk->get($stateFile);
            if (!empty($content)) {
                $existingState = json_decode($content, true);
                if (is_array($existingState)) {
                    $state['probes'] = Arr::get($existingState, 'probes', []);
                }
            }
        }

        $probeKey = sprintf('%s %s', Str::upper($method), $url);
        $probeState = $state['probes'][$probeKey] ?? [
            'last_seen_executed_at' => null,
            'consecutive_failures' => 0,
            'consecutive_slow' => 0,
            'last_notified_failure_at' => null,
            'last_notified_slow_at' => null,
        ];

        $probeState['last_seen_executed_at'] = now()->toDateTimeString();
        if (Arr::get($result, 'probe_status') === 'unreachable') {
            $probeState['consecutive_failures']++;
        } else {
            // If it's something else but not reachable (e.g. error, but we only have reachable/unreachable/error)
            // The requirement says "Sobald ein unreachable Status erscheint, soll zur passenden Probe eine Eintrag aktualisiert werden."
            // But it also says "In dieser state.json werden fehlgeschlagene Abrufe ... gespeichert"
            $probeState['consecutive_failures']++;
        }

        $state['probes'][$probeKey] = $probeState;
        $state['updated_at'] = now()->toDateTimeString();

        $disk->put($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    }

    /**
     * Sanitizes a given project name.
     *
     * @param string $project
     * @return string
     */
    private function sanitizeProjectName(string $project): string
    {
        if (method_exists($this->sanitizer, 'slug')) {
            return (string)$this->sanitizer->slug($project);
        }

        return preg_replace('/[^a-z0-9\-_]+/', '-', strtolower(trim($project)))
            ?: 'default';
    }
}