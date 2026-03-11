<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Storage\FileSettingsStorage;
use Uplinkr\Support\CliIcon;

class UplinkrSettingsCommand extends Command
{
    protected $signature = 'uplinkr:settings
                            {--iam-alive-enabled= : Enable or disable I\'m alive (true/false)}
                            {--iam-alive-interval-hours= : I\'m alive interval in hours (1-24)}
                            {--iam-alive-channels= : Comma separated channels (mail,log,webhook)}
                            {--force : Force execution without confirmation}';

    protected $description = 'Configure global Uplinkr settings (including I\'m alive).';

    /**
     * @param FileSettingsStorage $settingsStorage
     * @return int
     * @throws JsonException
     */
    public function handle(FileSettingsStorage $settingsStorage): int
    {
        $enabledOption = $this->option('iam-alive-enabled');
        $intervalOption = $this->option('iam-alive-interval-hours');
        $channelsOption = $this->option('iam-alive-channels');
        $force = (bool)$this->option('force');

        if ($enabledOption === null && $intervalOption === null && $channelsOption === null) {
            $current = $settingsStorage->getIamAliveSettings();

            $this->line(__('uplinkr::messages.settings_iam_alive_current_enabled', [
                'enabled' => $this->boolLabel((bool)$current['enabled'])
            ]));
            $this->line(__('uplinkr::messages.settings_iam_alive_current_interval', [
                'interval' => (int)$current['interval_hours']
            ]));
            $this->line(__('uplinkr::messages.settings_iam_alive_current_channels', [
                'channels' => implode(',', (array)$current['channels'])
            ]));
            $this->line(__('uplinkr::messages.settings_iam_alive_current_last_sent_at', [
                'lastSentAt' => (string)($current['last_sent_at'] ?? 'n/a')
            ]));

            return CommandAlias::SUCCESS;
        }

        $enabled = $this->parseEnabledOption($enabledOption);
        $interval = $intervalOption !== null ? (int)$intervalOption : null;
        $channels = $channelsOption !== null ? $this->parseChannels((string)$channelsOption) : null;

        if ($enabledOption !== null && $enabled === null) {
            $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.settings_validation_failed')));
            return CommandAlias::INVALID;
        }

        $validator = Validator::make(
            [
                'enabled' => $enabled,
                'interval_hours' => $interval,
                'channels' => $channels,
            ],
            [
                'enabled' => 'nullable|boolean',
                'interval_hours' => 'nullable|integer|min:1|max:24',
                'channels' => 'nullable|array|min:1',
                'channels.*' => 'in:mail,log,webhook',
            ]
        );

        if ($validator->fails()) {
            $this->error(CliIcon::ERROR->label(text: __('uplinkr::messages.settings_validation_failed')));
            return CommandAlias::INVALID;
        }

        if (!$force && !$this->confirm(__('uplinkr::messages.settings_iam_alive_confirm'))) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.common_process_aborted')));
            return CommandAlias::INVALID;
        }

        if ($enabled === null && ($interval !== null || $channels !== null)) {
            $enabled = true;
        }

        $updated = $settingsStorage->updateIamAliveSettings($enabled, $interval, $channels);

        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.settings_iam_alive_updated')));
        $this->line(__('uplinkr::messages.settings_iam_alive_current_enabled', [
            'enabled' => $this->boolLabel((bool)$updated['enabled'])
        ]));
        $this->line(__('uplinkr::messages.settings_iam_alive_current_interval', [
            'interval' => (int)$updated['interval_hours']
        ]));
        $this->line(__('uplinkr::messages.settings_iam_alive_current_channels', [
            'channels' => implode(',', (array)$updated['channels'])
        ]));
        $this->line(__('uplinkr::messages.settings_iam_alive_current_last_sent_at', [
            'lastSentAt' => (string)($updated['last_sent_at'] ?? 'n/a')
        ]));

        return CommandAlias::SUCCESS;
    }

    /**
     * @param string|null $value
     * @return bool|null
     */
    private function parseEnabledOption(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param string $channels
     * @return array
     */
    private function parseChannels(string $channels): array
    {
        $parts = explode(',', strtolower($channels));
        $parts = array_map(static fn(string $channel): string => trim($channel), $parts);
        $parts = array_values(array_filter($parts, static fn(string $channel): bool => $channel !== ''));

        return array_values(array_unique($parts));
    }

    /**
     * @param bool $value
     * @return string
     */
    private function boolLabel(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
