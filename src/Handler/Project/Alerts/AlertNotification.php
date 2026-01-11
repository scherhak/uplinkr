<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
//use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Arr;
use Uplinkr\Support\Logger;

/**
 * Class ProjectAlertNotification
 * @package Uplinkr\Handler\Project\Alerts
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AlertNotification extends Notification
{
    use Queueable;

    /**
     * @param array $alertData
     */
    public function __construct(
        private readonly array $alertData
    )
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        $channels = Arr::get($this->alertData, 'alert.channels', ['log']);

        // Map custom channel names to Laravel notification channels
        $mappedChannels = array_map(static function ($channel) {
            return match ($channel) {
                'mail' => 'mail',
                'log' => 'log',
                'webhook' => 'webhook',
                default => $channel,
            };
        }, $channels);

        return array_unique($mappedChannels);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $projectName = Arr::get($this->alertData, 'project');
        $probeName = Arr::get($this->alertData, 'probe');
        $reason = Arr::get($this->alertData, 'reason');
        $count = Arr::get($this->alertData, 'count');

        return (new MailMessage)
            ->error()
            ->subject(sprintf('Alert: Project "%s" - Probe "%s" failed', $projectName, $probeName))
            ->greeting(sprintf('Alert triggered for project "%s"', $projectName))
            ->line(sprintf('Probe: %s', $probeName))
            ->line(sprintf('Reason: %s', $reason))
            ->line(sprintf('Failure count: %d', $count))
            ->line('Please check your project monitoring dashboard for more details.');
    }

    /**
     * Get the log representation of the notification (default).
     *
     * @param mixed $notifiable
     * @return void
     */
    public function toLog(mixed $notifiable): void
    {
        $projectName = Arr::get($this->alertData, 'project');
        $probeName = Arr::get($this->alertData, 'probe');
        $reason = Arr::get($this->alertData, 'reason');
        $count = Arr::get($this->alertData, 'count');

        Logger::log()->warning(
            sprintf(
                'Alert triggered for project "%s" on probe "%s". Reason: %s (%d failures)',
                $projectName,
                $probeName,
                $reason,
                $count
            ),
            $this->alertData
        );
    }

    public function toWebhook(mixed $notifiable): void
    {
        
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return $this->alertData;
    }
}