<?php

namespace Uplinkr\Handler\Project;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class ListHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ListHandler
{
    /**
     * Constructor method.
     *
     * @param UplinkrConfig $config Configuration instance.
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
    }

    /**
     * Retrieves a list of all directories within the specified storage path.
     *
     * @return array An array of directory paths present in the configured storage disk and path.
     */
    public function all(): array
    {
        return Storage::disk($this->config->getStorageDisc())->directories($this->config->getStoragePath());
    }

    /**
     * Retrieves project details based on the project settings and state files.
     *
     * @return array<int, array{
     *     project: string,
     *     label: string,
     *     status: string,
     *     description: string|null,
     *     alerts: array,
     *     probes: array,
     *     state: array{total_failures: int, last_notification_at: string|null}
     * }>
     */
    public function allWithDetails(): array
    {
        $disk = Storage::disk($this->config->getStorageDisc());
        $projects = [];

        foreach ($this->all() as $projectPath) {
            $settingsPath = $this->buildProjectFilename($projectPath, 'settings');
            if (!$disk->exists($settingsPath)) {
                continue;
            }

            $settings = $this->decodeJson($disk->get($settingsPath));
            if (!$settings) {
                continue;
            }

            $projectName = Arr::get($settings, 'project', basename($projectPath));
            $label = Arr::get($settings, 'label', '-');
            $status = Arr::get($settings, 'status', 'enabled');
            $description = Arr::get($settings, 'description');
            $alerts = Arr::get($settings, 'alerts', []);
            $probes = Arr::get($settings, 'probes', []);

            $statePath = $this->buildProjectFilename($projectPath, 'state');
            $state = $disk->exists($statePath)
                ? $this->decodeJson($disk->get($statePath))
                : [];

            $projects[] = [
                'project' => is_string($projectName) ? $projectName : basename($projectPath),
                'label' => is_string($label) ? $label : '-',
                'status' => is_string($status) && trim($status) !== '' ? $status : 'enabled',
                'description' => is_string($description) && trim($description) !== '' ? $description : null,
                'alerts' => is_array($alerts) ? $alerts : [],
                'probes' => is_array($probes) ? $probes : [],
                'state' => $this->buildStateSummary(is_array($state) ? $state : []),
            ];
        }

        return $projects;
    }

    /**
     * Retrieves the count of probe files stored in the specified path.
     *
     * @param string $path The base directory path where probe files are located.
     * @return int The total number of probe files found in the specified directory.
     */
    public function countProbes(string $path): int
    {
        return count(Storage::disk($this->config->getStorageDisc())->allFiles(
            sprintf('%s/%s',
                $path,
                $this->config->getProbeResultsPath()
            )));
    }

    /**
     * @param string $content
     * @return array|null
     */
    private function decodeJson(string $content): ?array
    {
        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array $state
     * @return array{total_failures: int, last_notification_at: string|null}
     */
    private function buildStateSummary(array $state): array
    {
        $probes = Arr::get($state, 'probes', []);
        if (!is_array($probes)) {
            return [
                'total_failures' => 0,
                'last_notification_at' => null,
            ];
        }

        $totalFailures = 0;
        $lastNotification = null;

        foreach ($probes as $probeState) {
            if (!is_array($probeState)) {
                continue;
            }

            $totalFailures += (int) Arr::get($probeState, 'total_failures', 0);

            $lastFailure = Arr::get($probeState, 'last_notified_failure_at');
            if (is_string($lastFailure) && ($lastNotification === null || strcmp($lastFailure, $lastNotification) > 0)) {
                $lastNotification = $lastFailure;
            }

            $lastSlow = Arr::get($probeState, 'last_notified_slow_at');
            if (is_string($lastSlow) && ($lastNotification === null || strcmp($lastSlow, $lastNotification) > 0)) {
                $lastNotification = $lastSlow;
            }
        }

        return [
            'total_failures' => $totalFailures,
            'last_notification_at' => $lastNotification,
        ];
    }

    /**
     * @param string $projectPath
     * @param string $basename
     * @return string
     */
    private function buildProjectFilename(string $projectPath, string $basename): string
    {
        return sprintf('%s/%s.%s', $projectPath, $basename, $this->config->getFileExtension());
    }
}
