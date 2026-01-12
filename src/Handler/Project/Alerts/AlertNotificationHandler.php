<?php

namespace Uplinkr\Handler\Project\Alerts;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
//use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use JsonException;
use Uplinkr\Support\Logger;

/**
 * Class ProjectAlertNotification
 * @package Uplinkr\Handler\Project\Alerts
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AlertNotificationHandler extends Notification
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
                'log' => 'uplinkr-log',
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

    /**
     * Get the webhook representation of the notification.
     *
     * @param mixed $notifiable
     * @return void
     * @throws JsonException
     */
    public function toWebhook(mixed $notifiable): void
    {
        $config = config('uplinkr.notifications.channels.webhook');

        if (!Arr::get($config, 'enabled', false)) {
            return;
        }

        $url = Arr::get($config, 'url');
        if (!$url) {
            Logger::log()->error('Webhook URL is not configured in uplinkr.php');
            return;
        }

        $method = strtoupper(Arr::get($config, 'method', 'POST'));
        $timeout = Arr::get($config, 'timeout_seconds', 10);
        $connectTimeout = Arr::get($config, 'connect_timeout_seconds', 5);
        $verifyTls = Arr::get($config, 'verify_tls', true);
        $headers = Arr::get($config, 'headers', []);
        $payload = $this->toArray($notifiable);

        // Version the payload if configured
        $version = config('uplinkr.notifications.payload.version');
        if ($version) {
            $payload = [
                'version' => $version,
                'data' => $payload,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        $pendingRequest = Http::withHeaders($headers)
            ->timeout($timeout)
            ->connectTimeout($connectTimeout);

        if (!$verifyTls) {
            $pendingRequest->withoutVerifying();
        }

        // Retry logic
        $retryConfig = Arr::get($config, 'retry', []);
        $maxAttempts = Arr::get($retryConfig, 'max_attempts', 1);
        $backoff = Arr::get($retryConfig, 'backoff_ms', []);

        if ($maxAttempts > 1) {
            $pendingRequest->retry($maxAttempts, function ($attempt, $exception) use ($backoff) {
                if (is_array($backoff)) {
                    return $backoff[$attempt - 1] ?? end($backoff);
                }
                return is_numeric($backoff) ? (int)$backoff : 0;
            });
        }

        // Signing
        $signingConfig = Arr::get($config, 'signing', []);
        if (Arr::get($signingConfig, 'enabled', false)) {
            $secret = Arr::get($signingConfig, 'secret');
            $headerName = Arr::get($signingConfig, 'header', 'X-Uplinkr-Signature');
            $algo = Arr::get($signingConfig, 'algo', 'sha256');

            if ($secret) {
                $signature = hash_hmac($algo, json_encode($payload, JSON_THROW_ON_ERROR), $secret);
                $pendingRequest->withHeaders([
                    $headerName => sprintf('%s=%s', $algo, $signature)
                ]);
            }
        }

        try {
            $response = $pendingRequest->send($method, $url, [
                'json' => $payload
            ]);

            if ($response->failed()) {
                Logger::log()->error(
                    sprintf('Webhook notification failed with status %d', $response->status()),
                    ['response' => $response->body(), 'url' => $url]
                );
            }
        } catch (\Exception $e) {
            Logger::log()->error(
                sprintf('Webhook notification failed: %s', $e->getMessage()),
                ['url' => $url]
            );
        }
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