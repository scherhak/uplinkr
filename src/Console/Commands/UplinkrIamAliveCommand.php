<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;
use Uplinkr\Handler\IamAlive\IamAliveSummaryHandler;
use Uplinkr\Notifications\IamAliveNotification;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileSettingsStorage;
use Uplinkr\Support\CliIcon;
use Uplinkr\Support\Time;

class UplinkrIamAliveCommand extends Command
{
    protected $signature = 'uplinkr:iam-alive
                            {--scheduled : Internal scheduler run that respects iam_alive interval_hours}';

    protected $description = 'Send an "I\'m alive" status notification.';

    /**
     * @param FileSettingsStorage $settingsStorage
     * @param IamAliveSummaryHandler $summaryHandler
     * @return int
     * @throws JsonException
     */
    public function handle(FileSettingsStorage $settingsStorage, IamAliveSummaryHandler $summaryHandler): int
    {
        $config = UplinkrConfig::fromConfig();
        $settings = $settingsStorage->getIamAliveSettings();
        $enabled = (bool)($settings['enabled'] ?? false);

        if (!$enabled) {
            $this->line(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_disabled')));
            return CommandAlias::SUCCESS;
        }

        if ($this->option('scheduled') && !$this->isDueForScheduledRun($settings)) {
            $this->line(CliIcon::INFO->label(text: __('uplinkr::messages.iam_alive_not_due')));
            return CommandAlias::SUCCESS;
        }

        $channels = (array)($settings['channels'] ?? ['mail']);
        $channels = array_values(array_intersect($channels, ['mail', 'log', 'webhook']));

        $recipients = [];
        if (in_array('mail', $channels, true)) {
            $recipients = $config->getMailTo();

            if (empty($recipients)) {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_no_recipients')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'mail'));
            } elseif (!$config->isMailEnabled()) {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_mail_channel_disabled')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'mail'));
            }
        }

        if (in_array('webhook', $channels, true)) {
            if (!$config->isWebhookEnabled()) {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_webhook_channel_disabled')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'webhook'));
            } elseif (!is_string($config->getWebhookUrl()) || trim((string)$config->getWebhookUrl()) === '') {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_webhook_url_missing')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'webhook'));
            }
        }

        if (empty($channels)) {
            if ($this->option('scheduled')) {
                $settingsStorage->markIamAliveAttempted(Time::now());
            }

            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_no_channels')));
            return CommandAlias::SUCCESS;
        }

        $notifiable = new AnonymousNotifiable;
        if (!empty($recipients)) {
            $notifiable->route('mail', $recipients);
        }

        $sentAt = Time::now();
        $summary = $summaryHandler->handle();
        $iamAliveSettingsForMessage = Arr::only($settings, [
            'enabled',
            'interval_hours',
            'channels',
            'last_sent_at',
        ]);
        Arr::set($iamAliveSettingsForMessage, 'channels', $channels);
        Arr::set($iamAliveSettingsForMessage, 'last_sent_at', $sentAt);

        $payload = [
            'summary' => $summary,
            'settings' => [
                'iam_alive' => $iamAliveSettingsForMessage,
            ],
            'sent_at' => $sentAt,
        ];

        $notifiable->notify(new IamAliveNotification(channels: $channels, payload: $payload));
        $settingsStorage->markIamAliveSent($sentAt);

        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.iam_alive_sent', [
            'channels' => implode(',', $channels)
        ])));

        return CommandAlias::SUCCESS;
    }

    /**
     * @param array $settings
     * @return bool
     */
    private function isDueForScheduledRun(array $settings): bool
    {
        $intervalHours = (int)($settings['interval_hours'] ?? 24);
        if ($intervalHours < 1 || $intervalHours > 24) {
            return false;
        }

        $lastActivityAt = $this->resolveLastActivityAt($settings);
        if ($lastActivityAt === null) {
            return true;
        }

        try {
            return Carbon::parse($lastActivityAt)
                ->addHours($intervalHours)
                ->lessThanOrEqualTo(now());
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * @param array $settings
     * @return string|null
     */
    private function resolveLastActivityAt(array $settings): ?string
    {
        $candidates = array_filter([
            $this->normalizeTimestamp(Arr::get($settings, 'last_sent_at')),
            $this->normalizeTimestamp(Arr::get($settings, 'last_attempted_at')),
        ]);

        if ($candidates === []) {
            return null;
        }

        rsort($candidates);

        return $candidates[0];
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeTimestamp(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
