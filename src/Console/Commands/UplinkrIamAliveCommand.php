<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
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
            } elseif (!config('uplinkr.notifications.channels.mail.enabled', false)) {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_mail_channel_disabled')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'mail'));
            }
        }

        if (in_array('webhook', $channels, true)) {
            if (!config('uplinkr.notifications.channels.webhook.enabled', false)) {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_webhook_channel_disabled')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'webhook'));
            } elseif (!is_string(config('uplinkr.notifications.channels.webhook.url')) || trim((string)config('uplinkr.notifications.channels.webhook.url')) === '') {
                $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_webhook_url_missing')));
                $channels = array_values(array_filter($channels, static fn(string $channel): bool => $channel !== 'webhook'));
            }
        }

        if (empty($channels)) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_no_channels')));
            return CommandAlias::SUCCESS;
        }

        $notifiable = new AnonymousNotifiable;
        if (!empty($recipients)) {
            $notifiable->route('mail', $recipients);
        }

        $sentAt = Time::now();
        $summary = $summaryHandler->handle();
        $iamAliveSettingsForMessage = $settings;
        $iamAliveSettingsForMessage['channels'] = $channels;
        $iamAliveSettingsForMessage['last_sent_at'] = $sentAt;

        $payload = [
            'summary' => $summary,
            'settings' => [
                'iam_alive' => $iamAliveSettingsForMessage,
            ],
            'sent_at' => $sentAt,
        ];

        $notifiable->notify(new IamAliveNotification($channels, $payload));
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

        $lastSentAt = $settings['last_sent_at'] ?? null;
        if (!is_string($lastSentAt) || trim($lastSentAt) === '') {
            return true;
        }

        try {
            return Carbon::parse($lastSentAt)
                ->addHours($intervalHours)
                ->lessThanOrEqualTo(now());
        } catch (\Throwable) {
            return true;
        }
    }
}
