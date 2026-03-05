<?php

namespace Uplinkr\Handler\IamAlive;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Logger;

class IamAliveSummaryHandler
{
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
    }

    /**
     * @return array{
     *     active_projects: int,
     *     configured_probes: int,
     *     successful_checks: int,
     *     failed_checks: int
     * }
     */
    public function handle(): array
    {
        $disk = Storage::disk($this->config->getStorageDisc());
        $directories = $disk->directories($this->config->getStoragePath());
        $extension = $this->config->getFileExtension();

        $activeProjects = 0;
        $configuredProbes = 0;
        $successfulChecks = 0;
        $failedChecks = 0;

        foreach ($directories as $directory) {
            $settingsPath = sprintf('%s/settings.%s', $directory, $extension);
            if ($disk->exists($settingsPath)) {
                $settings = $this->decodeJsonOrNull($disk->get($settingsPath), $settingsPath);

                if (is_array($settings)) {
                    $status = Arr::get($settings, 'status', 'enabled');
                    if ($status === 'enabled') {
                        $activeProjects++;
                    }

                    $probes = Arr::get($settings, 'probes', []);
                    if (is_array($probes)) {
                        $configuredProbes += count($probes);
                    }
                }
            }

            $statePath = sprintf('%s/state.%s', $directory, $extension);
            if (!$disk->exists($statePath)) {
                continue;
            }

            $state = $this->decodeJsonOrNull($disk->get($statePath), $statePath);
            if (!is_array($state)) {
                continue;
            }

            $probesState = Arr::get($state, 'probes', []);
            if (!is_array($probesState)) {
                continue;
            }

            foreach ($probesState as $probeState) {
                if (!is_array($probeState)) {
                    continue;
                }

                $consecutiveFailures = (int)Arr::get($probeState, 'consecutive_failures', 0);
                if ($consecutiveFailures > 0) {
                    $failedChecks++;
                } else {
                    $successfulChecks++;
                }
            }
        }

        return [
            'active_projects' => $activeProjects,
            'configured_probes' => $configuredProbes,
            'successful_checks' => $successfulChecks,
            'failed_checks' => $failedChecks,
        ];
    }

    /**
     * @param string $content
     * @param string $path
     * @return array|null
     */
    private function decodeJsonOrNull(string $content, string $path): ?array
    {
        if (trim($content) === '') {
            return null;
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (JsonException $exception) {
            Logger::log()->warning('Unable to decode JSON file while building I\'m alive summary.', [
                'path' => $path,
                'reason' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
