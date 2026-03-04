<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use JsonException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Notifications\IamAliveNotification;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileSettingsStorage;
use Uplinkr\Support\CliIcon;

class UplinkrIamAliveCommand extends Command
{
    protected $signature = 'uplinkr:iam-alive';

    protected $description = 'Send an "I\'m alive" status notification.';

    /**
     * @param UplinkrConfig $config
     * @param FileSettingsStorage $settingsStorage
     * @return int
     * @throws JsonException
     */
    public function handle(UplinkrConfig $config, FileSettingsStorage $settingsStorage): int
    {
        $settings = $settingsStorage->getIamAliveSettings();
        $enabled = (bool)($settings['enabled'] ?? false);

        if (!$enabled) {
            $this->line(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_disabled')));
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

        if (empty($channels)) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_no_channels')));
            return CommandAlias::SUCCESS;
        }

        $notifiable = new AnonymousNotifiable;
        if (!empty($recipients)) {
            $notifiable->route('mail', $recipients);
        }
        $notifiable->notify(new IamAliveNotification($channels));

        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.iam_alive_sent', [
            'channels' => implode(',', $channels)
        ])));

        return CommandAlias::SUCCESS;
    }
}
