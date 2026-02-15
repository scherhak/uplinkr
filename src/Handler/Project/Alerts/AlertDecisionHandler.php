<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Logger;
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
     * Handles the processing of alerts and decisions for a specific project or all projects.
     *
     * @param string|null $projectName The name of the project to handle, or null to handle all projects.
     * @return array The decisions resulting from the handling process.
     * @throws JsonException
     */
    public function handle(?string $projectName = null): array
    {
        if ($projectName === null) {
            return $this->handleAllProjects();
        }

        $config = $this->loadProjectConfiguration($projectName);
        if ($config === null) {
            return [];
        }

        $state = $this->loadState($projectName);
        if (empty($state)) {
            return [];
        }

        $result = $this->processProbeAlerts(
            $projectName,
            $state,
            $config['alerts'],
            $config['default_cooldown']
        );

        if ($result['state_updated']) {
            $this->saveState($projectName, $state);
        }

        $this->notifyGroupedDecisions($result['decisions']);

        return $result['decisions'];
    }

    /**
     * Saves the state data for a specified project to persistent storage.
     *
     * @param string $projectName The name of the project whose state is being saved.
     * @param array $state The state data to be saved.
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
     * Loads the state of a project from the storage.
     *
     * @param string $projectName The name of the project whose state needs to be loaded.
     * @return array An associative array representing the state of the project. Returns an empty array if the state file does not exist or is empty.
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
     * Sanitizes the given project name.
     *
     * @param string $project The name of the project to be sanitized.
     * @return string The sanitized project name.
     */
    private function sanitizeProjectName(string $project): string
    {
        return $this->sanitizer->project($project);
    }

    /**
     * Handles alert decisions for all projects by processing each one and merging their decisions.
     *
     * @return array Combined array of alert decisions from all projects
     * @throws JsonException
     */
    private function handleAllProjects(): array
    {
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

    /**
     * Loads the configuration for a specific project.
     *
     * @param string $projectName Name of the project whose configuration should be loaded.
     * @return array|null Returns the project configuration array if found, or null if the project does not exist.
     * @throws JsonException
     */
    private function loadProjectConfiguration(string $projectName): ?array
    {
        $projectSettings = $this->projectStorage->findProject($projectName);
        if (!$projectSettings) {
            return null;
        }

        $alertsSettings = Arr::get($projectSettings, 'alerts', Arr::get($projectSettings, 'alarms', []));
        $cooldownMinutes = Arr::get($alertsSettings, 'cooldown_minutes');

        return [
            'alerts' => $alertsSettings,
            'default_cooldown' => $cooldownMinutes
        ];
    }

    /**
     * Determines if the alert is enabled.
     *
     * @param array $alert The alert configuration array.
     * @return bool Returns true if the alert is enabled, false otherwise.
     */
    private function isAlertEnabled(array $alert): bool
    {
        return Arr::get($alert, 'enabled') === true;
    }

    /**
     * Checks if a probe is currently within its cooldown period.
     *
     * @param array $probeData Data associated with the probe.
     * @param int|null $cooldownMinutes The cooldown period in minutes. If null, no cooldown is applied.
     * @return bool Returns true if the current time is within the cooldown period, false otherwise.
     */
    private function isInCooldownPeriod(array $probeData, ?int $cooldownMinutes): bool
    {
        if ($cooldownMinutes === null) {
            return false;
        }

        $lastNotifiedAt = Arr::get($probeData, 'last_notified_failure_at');
        if (!$lastNotifiedAt) {
            return false;
        }

        $lastNotifiedAt = Carbon::parse($lastNotifiedAt);

        return $lastNotifiedAt->addMinutes($cooldownMinutes)->isFuture();
    }

    /**
     * Determines if an alert should be triggered based on failures and cooldown.
     *
     * @param array $probeData
     * @param array $alert
     * @return bool
     */
    private function shouldTriggerAlert(array $probeData, array $alert): bool
    {
        $consecutiveFailures = Arr::get($probeData, 'consecutive_failures', 0);
        $triggerAfterFailures = Arr::get($alert, 'trigger_after_failures', 3);

        if ($consecutiveFailures < $triggerAfterFailures) {
            return false;
        }

        $cooldownMinutes = Arr::get($alert, 'cooldown_minutes');
        return !$this->isInCooldownPeriod($probeData, $cooldownMinutes);
    }

    /**
     * Creates an alert decision array and logs the alert.
     *
     * @param string $projectName
     * @param string $probeKey
     * @param array $alert
     * @param array $probeData
     * @return array
     */
    private function createAlertDecision(string $projectName, string $probeKey, array $alert, array $probeData): array
    {
        $consecutiveFailures = Arr::get($probeData, 'consecutive_failures', 0);

        Logger::log()->warning(sprintf(
            'Alert triggered for project "%s" on probe "%s". Reason: %s (%d failures)',
            $projectName,
            $probeKey,
            'consecutive_failures',
            $consecutiveFailures
        ));

        return [
            'project' => $projectName,
            'probe' => $probeKey,
            'alert' => $alert,
            'reason' => 'consecutive_failures',
            'count' => $consecutiveFailures,
            'probe_tls_expiration_date' => Arr::get($probeData, 'probe_tls_expiration_date'),
        ];
    }

    /**
     * Updates the state of a probe by adjusting its failure counters and timestamp.
     *
     * @param array &$state Reference to the array containing the current state.
     * @param string $probeKey The key identifying the specific probe to update.
     * @param int $triggerAfterFailures The number of failures to increment after a trigger.
     * @param int $consecutiveFailures The current count of consecutive failures.
     * @return void
     */
    private function updateProbeState(array &$state, string $probeKey, int $triggerAfterFailures, int $consecutiveFailures): void
    {
        $probeData = $state['probes'][$probeKey] ?? [];
        $totalFailures = $probeData['total_failures'] ?? null;

        if ($totalFailures === null) {
            $totalFailures = $consecutiveFailures;
        } else {
            $totalFailures += $triggerAfterFailures;
        }

        $state['probes'][$probeKey]['total_failures'] = $totalFailures;
        $state['probes'][$probeKey]['consecutive_failures'] = 0;
        $state['probes'][$probeKey]['last_notified_failure_at'] = Time::now();
    }

    /**
     * Processes probe alerts, evaluates conditions, updates state, and generates decisions for triggered alerts.
     *
     * @param string $projectName The name of the project associated with the alerts.
     * @param array &$state Reference to the current state of probes, used to evaluate and update probe information.
     * @param array $alerts The list of alert configurations to be processed.
     * @param int|null $defaultCooldown The default cooldown period (in minutes) to apply if not specified in the alert configuration.
     * @return array Returns an array containing the alert decisions and a flag indicating if the state was updated:
     *               - 'decisions': An array of generated decisions for triggered alerts.
     *               - 'state_updated': A boolean indicating if the state was updated.
     */
    private function processProbeAlerts(string $projectName, array &$state, array $alerts, ?int $defaultCooldown): array
    {
        $decisions = [];
        $stateUpdated = false;

        foreach ($alerts as $alert) {
            if (!is_array($alert) || !$this->isAlertEnabled($alert)) {
                continue;
            }

            $currentCooldownMinutes = Arr::get($alert, 'cooldown_minutes', $defaultCooldown);

            foreach (Arr::get($state, 'probes', []) as $probeKey => $probeData) {
                if (!$this->shouldTriggerAlert($probeData, array_merge(['cooldown_minutes' => $currentCooldownMinutes], $alert))) {
                    continue;
                }

                $decisions[] = $this->createAlertDecision($projectName, $probeKey, $alert, $probeData);

                $triggerAfterFailures = Arr::get($alert, 'trigger_after_failures', 3);
                $consecutiveFailures = Arr::get($probeData, 'consecutive_failures', 0);

                $this->updateProbeState($state, $probeKey, $triggerAfterFailures, $consecutiveFailures);
                $stateUpdated = true;
            }
        }

        return [
            'decisions' => $decisions,
            'state_updated' => $stateUpdated
        ];
    }

    /**
     * Sends aggregated notifications grouped by project and alert configuration.
     *
     * @param array $decisions
     * @return void
     */
    private function notifyGroupedDecisions(array $decisions): void
    {
        if (empty($decisions)) {
            return;
        }

        $grouped = [];

        foreach ($decisions as $decision) {
            $alert = Arr::get($decision, 'alert', []);
            $groupKey = md5(json_encode([
                'project' => Arr::get($decision, 'project'),
                'alert' => $alert,
            ]));

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'project' => Arr::get($decision, 'project'),
                    'alert' => $alert,
                    'probes' => [],
                ];
            }

            $grouped[$groupKey]['probes'][] = [
                'probe' => Arr::get($decision, 'probe'),
                'reason' => Arr::get($decision, 'reason'),
                'count' => Arr::get($decision, 'count'),
                'probe_tls_expiration_date' => Arr::get($decision, 'probe_tls_expiration_date'),
            ];
        }

        $notifiable = new AnonymousNotifiable;
        $mailRecipients = $this->config->getMailTo();
        if (!empty($mailRecipients)) {
            $notifiable->route('mail', $mailRecipients);
        }

        foreach ($grouped as $notificationData) {
            $notifiable->notify(new AlertNotificationHandler($notificationData));
        }
    }
}
