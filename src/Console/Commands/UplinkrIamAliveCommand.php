<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Uplinkr\Notifications\IamAliveNotification;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\CliIcon;

class UplinkrIamAliveCommand extends Command
{
    protected $signature = 'uplinkr:iam-alive';

    protected $description = 'Send an "I\'m alive" status email notification.';

    public function handle(UplinkrConfig $config): int
    {
        if (!config('uplinkr.notifications.channels.mail.enabled', false)) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_mail_channel_disabled')));
            return CommandAlias::SUCCESS;
        }

        $recipients = $config->getMailTo();
        if (empty($recipients)) {
            $this->warn(CliIcon::WARN->label(text: __('uplinkr::messages.iam_alive_no_recipients')));
            return CommandAlias::SUCCESS;
        }

        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $recipients);
        $notifiable->notify(new IamAliveNotification());

        $this->info(CliIcon::OK->label(text: __('uplinkr::messages.iam_alive_sent', ['count' => count($recipients)])));

        return CommandAlias::SUCCESS;
    }
}
