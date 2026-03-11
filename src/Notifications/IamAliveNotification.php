<?php

namespace Uplinkr\Notifications;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Logger;
use Uplinkr\Support\Time;

class IamAliveNotification extends Notification
{
    /**
     * @param array $channels
     * @param array{
     *     summary?: array{
     *         active_projects?: int,
     *         configured_probes?: int,
     *         successful_checks?: int,
     *         failed_checks?: int
     *     },
     *     settings?: array,
     *     sent_at?: string
     * } $payload
     */
    public function __construct(
        private readonly array $channels = ['mail'],
        private readonly array $payload = []
    )
    {
    }

    public function via(mixed $notifiable): array
    {
        return array_values(array_unique(array_map(static function (string $channel): string {
            return match ($channel) {
                'mail' => 'mail',
                'log' => 'uplinkr-log',
                'webhook' => 'uplinkr-webhook',
                default => $channel,
            };
        }, $this->channels)));
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $config = UplinkrConfig::fromConfig();
        $subjectPrefix = $config->getMailSubjectPrefix();

        $message = (new MailMessage)
            ->subject(__('uplinkr::messages.iam_alive_mail_subject', ['prefix' => $subjectPrefix]))
            ->line(__('uplinkr::messages.iam_alive_mail_line'))
            ->line(__('uplinkr::messages.iam_alive_mail_all_ok'))
            ->line(__('uplinkr::messages.iam_alive_mail_settings_head'))
            ->line(__('uplinkr::messages.iam_alive_mail_settings_interval_hours', [
                'hours' => (int)Arr::get($this->payload, 'settings.iam_alive.interval_hours', 24)
            ]))
            ->line(__('uplinkr::messages.iam_alive_mail_settings_channels', [
                'channels' => implode(',', (array)Arr::get($this->payload, 'settings.iam_alive.channels', []))
            ]))
            ->line(__('uplinkr::messages.iam_alive_mail_settings_last_sent_at', [
                'lastSentAt' => (string)Arr::get($this->payload, 'settings.iam_alive.last_sent_at', 'n/a')
            ]));

        if ($config->getMailMailer()) {
            $message->mailer($config->getMailMailer());
        }

        if ($config->getMailFromAddress()) {
            $message->from($config->getMailFromAddress(), $config->getMailFromName());
        }

        return $message;
    }

    public function toLog(mixed $notifiable): void
    {
        $summary = Arr::get($this->payload, 'summary', []);

        Logger::log()->info(
            sprintf(
                'Uplinkr heartbeat: active projects=%d, configured probes=%d, successful checks=%d, failed checks=%d',
                (int)Arr::get($summary, 'active_projects', 0),
                (int)Arr::get($summary, 'configured_probes', 0),
                (int)Arr::get($summary, 'successful_checks', 0),
                (int)Arr::get($summary, 'failed_checks', 0),
            ),
            $this->toArray($notifiable)
        );
    }

    public function toWebhook(mixed $notifiable): void
    {
        $config = UplinkrConfig::fromConfig();

        if (!$config->isWebhookEnabled()) {
            return;
        }

        $url = $config->getWebhookUrl();
        if (!$url) {
            Logger::log()->error('Webhook URL is not configured in uplinkr.php');
            return;
        }

        $requestConfig = [
            'url' => $url,
            'method' => strtoupper($config->getWebhookMethod()),
            'timeout' => $config->getWebhookTimeoutSeconds(),
            'connect_timeout' => $config->getWebhookConnectTimeoutSeconds(),
            'verify_tls' => $config->isWebhookVerifyTls(),
            'headers' => $config->getWebhookHeaders(),
        ];

        $payload = $this->toArray($notifiable);

        $pendingRequest = Http::withHeaders($requestConfig['headers'])
            ->timeout($requestConfig['timeout'])
            ->connectTimeout($requestConfig['connect_timeout']);

        if (!$requestConfig['verify_tls']) {
            $pendingRequest->withoutVerifying();
        }

        $retryConfig = $config->getWebhookRetry();
        $maxAttempts = Arr::get($retryConfig, 'max_attempts', 1);
        $backoff = Arr::get($retryConfig, 'backoff_ms', []);
        if ($maxAttempts > 1) {
            $pendingRequest->retry($maxAttempts, function ($attempt) use ($backoff) {
                if (is_array($backoff)) {
                    return $backoff[$attempt - 1] ?? end($backoff);
                }
                return is_numeric($backoff) ? (int)$backoff : 0;
            });
        }

        $this->applySigning($pendingRequest, $config, $payload);

        try {
            $response = $pendingRequest->send($requestConfig['method'], $requestConfig['url'], [
                'json' => $payload
            ]);

            if ($response->failed()) {
                Logger::log()->error(
                    sprintf('Webhook notification failed with status %d', $response->status()),
                    ['response' => $response->body(), 'url' => $requestConfig['url']]
                );
            }
        } catch (Exception $e) {
            Logger::log()->error(
                sprintf('Webhook notification failed: %s', $e->getMessage()),
                ['url' => $requestConfig['url']]
            );
        }
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'event' => 'iam_alive',
            'message' => 'Uplinkr is active.',
            'timestamp' => Arr::get($this->payload, 'sent_at', Time::now()),
            'channels' => $this->channels,
            'summary' => [
                'active_projects' => (int)Arr::get($this->payload, 'summary.active_projects', 0),
                'configured_probes' => (int)Arr::get($this->payload, 'summary.configured_probes', 0),
                'successful_checks' => (int)Arr::get($this->payload, 'summary.successful_checks', 0),
                'failed_checks' => (int)Arr::get($this->payload, 'summary.failed_checks', 0),
            ],
            'settings' => Arr::get($this->payload, 'settings', []),
        ];
    }

    /**
     * @param PendingRequest $pendingRequest
     * @param UplinkrConfig $config
     * @param array $payload
     * @return void
     */
    private function applySigning(PendingRequest $pendingRequest, UplinkrConfig $config, array $payload): void
    {
        $signingConfig = $config->getWebhookSigning();
        if (!Arr::get($signingConfig, 'enabled', false)) {
            return;
        }

        $secret = Arr::get($signingConfig, 'secret');
        if (!$secret) {
            return;
        }

        $headerName = Arr::get($signingConfig, 'header', 'X-Uplinkr-Signature');
        $algo = Arr::get($signingConfig, 'algo', 'sha256');
        $signature = hash_hmac($algo, json_encode($payload), $secret);

        $pendingRequest->withHeaders([
            $headerName => sprintf('%s=%s', $algo, $signature)
        ]);
    }
}
