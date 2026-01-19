<?php

namespace Uplinkr\Notifications\Channels;

use Illuminate\Notifications\Notification;

class UplinkrWebhookChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWebhook')) {
            return;
        }

        // Die toWebhook Methode im AlertNotificationHandler handhabt die komplette Webhook-Logik
        $notification->toWebhook($notifiable);
    }
}
