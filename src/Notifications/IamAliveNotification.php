<?php

namespace Uplinkr\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Uplinkr\Objects\Config\UplinkrConfig;

class IamAliveNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $config = UplinkrConfig::fromConfig();
        $subjectPrefix = $config->getMailSubjectPrefix();

        $message = (new MailMessage)
            ->subject(__('uplinkr::messages.iam_alive_mail_subject', ['prefix' => $subjectPrefix]))
            ->line(__('uplinkr::messages.iam_alive_mail_line'));

        if ($config->getMailMailer()) {
            $message->mailer($config->getMailMailer());
        }

        if ($config->getMailFromAddress()) {
            $message->from($config->getMailFromAddress(), $config->getMailFromName());
        }

        return $message;
    }
}
