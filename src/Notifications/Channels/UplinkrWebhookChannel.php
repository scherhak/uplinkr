<?php

namespace Uplinkr\Notifications\Channels;

use Illuminate\Notifications\Notification;

/**
 * Class UplinkrWebhookChannel
 * @package Uplinkr\Notifications\Channels
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class UplinkrWebhookChannel
{
    /**
     * Send the given notification via webhook.
     *
     * @param mixed $notifiable The notifiable entity
     * @param Notification $notification The notification to send
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWebhook')) {
            return;
        }

        $notification->toWebhook($notifiable);
    }
}
