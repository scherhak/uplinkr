<?php

namespace Uplinkr\Handler\Project\Alerts;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use JsonException;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Logger;

/**
 * Class ProjectAlertNotification
 * @package Uplinkr\Handler\Project\Alerts
 *
 * Notifications are sent synchronously by default.
 * If you want to queue notifications, implement the ShouldQueue interface.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AlertNotificationHandler extends Notification
{
    // Removed Queueable trait to send notifications synchronously

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
                'webhook' => 'uplinkr-webhook',
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
        $context = $this->resolveAlertContext();
        $config = UplinkrConfig::fromConfig();
        $mailConfig = $this->resolveMailConfig($config);

        $message = $this->buildMailMessage($context, $mailConfig);
        return $this->applyMailDeliveryConfig($message, $mailConfig);
    }

    /**
     * Get the log representation of the notification (default).
     *
     * @param mixed $notifiable
     * @return void
     */
    public function toLog(mixed $notifiable): void
    {
        $context = $this->resolveAlertContext();

        Logger::log()->warning(
            sprintf(
                'Alert triggered for project "%s" on probe "%s". Reason: %s (%d failures). TLS expiration date: %s',
                $context['project'],
                $context['probe'],
                $context['reason'],
                $context['count'],
                $context['probe_tls_expiration_date'] ?? 'n/a'
            ),
            $this->toArray($notifiable)
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
        $config = UplinkrConfig::fromConfig();

        if (!$config->isWebhookEnabled()) {
            return;
        }

        $url = $this->getWebhookUrlOrLog($config);
        if ($url === null) {
            return;
        }

        $requestConfig = $this->resolveWebhookRequestConfig($config, $url);
        $payload = $this->buildWebhookPayload($notifiable, $config);
        $pendingRequest = $this->buildPendingRequest($requestConfig);

        $this->applyRetry($pendingRequest, $config);
        $this->applySigning($pendingRequest, $config, $payload);
        $this->sendWebhook($pendingRequest, $requestConfig, $payload);
    }

    /**
     * Retrieves the webhook URL from the provided configuration.
     * Logs an error if the webhook URL is not configured.
     *
     * @param UplinkrConfig $config The configuration instance to fetch the webhook URL from.
     * @return string|null The webhook URL if configured, or null if not.
     */
    private function getWebhookUrlOrLog(UplinkrConfig $config): ?string
    {
        $url = $config->getWebhookUrl();
        if (!$url) {
            Logger::log()->error('Webhook URL is not configured in uplinkr.php');
            return null;
        }

        return $url;
    }

    /**
     * Resolves the configuration for a webhook request.
     *
     * @param UplinkrConfig $config Configuration object containing webhook settings.
     * @param string $url The URL to which the webhook request will be sent.
     * @return array An associative array containing the webhook request configuration.
     */
    private function resolveWebhookRequestConfig(UplinkrConfig $config, string $url): array
    {
        return [
            'url' => $url,
            'method' => strtoupper($config->getWebhookMethod()),
            'timeout' => $config->getWebhookTimeoutSeconds(),
            'connect_timeout' => $config->getWebhookConnectTimeoutSeconds(),
            'verify_tls' => $config->isWebhookVerifyTls(),
            'headers' => $config->getWebhookHeaders(),
        ];
    }

    /**
     * Build the webhook payload for the notification.
     *
     * @param mixed $notifiable The notifiable entity instance.
     * @param UplinkrConfig $config The configuration object used to generate the payload.
     * @return array The structured webhook payload.
     */
    private function buildWebhookPayload(mixed $notifiable, UplinkrConfig $config): array
    {
        $payload = $this->toArray($notifiable);
        $version = $config->getPayloadVersion();

        if (!$version) {
            return $payload;
        }

        return [
            'version' => $version,
            'data' => $payload,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @param array{timeout: int, connect_timeout: int, verify_tls: bool, headers: array} $requestConfig
     * @return PendingRequest
     */
    private function buildPendingRequest(array $requestConfig): PendingRequest
    {
        $pendingRequest = Http::withHeaders($requestConfig['headers'])
            ->timeout($requestConfig['timeout'])
            ->connectTimeout($requestConfig['connect_timeout']);

        if (!$requestConfig['verify_tls']) {
            $pendingRequest->withoutVerifying();
        }

        return $pendingRequest;
    }

    private function applyRetry(PendingRequest $pendingRequest, UplinkrConfig $config): void
    {
        $retryConfig = $config->getWebhookRetry();
        $maxAttempts = Arr::get($retryConfig, 'max_attempts', 1);
        $backoff = Arr::get($retryConfig, 'backoff_ms', []);

        if ($maxAttempts <= 1) {
            return;
        }

        $pendingRequest->retry($maxAttempts, function ($attempt, $exception) use ($backoff) {
            if (is_array($backoff)) {
                return $backoff[$attempt - 1] ?? end($backoff);
            }
            return is_numeric($backoff) ? (int)$backoff : 0;
        });
    }

    /**
     * @param PendingRequest $pendingRequest
     * @param UplinkrConfig $config
     * @param array $payload
     * @return void
     * @throws JsonException
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
        $signature = hash_hmac($algo, json_encode($payload, JSON_THROW_ON_ERROR), $secret);

        $pendingRequest->withHeaders([
            $headerName => sprintf('%s=%s', $algo, $signature)
        ]);
    }

    /**
     * @param PendingRequest $pendingRequest
     * @param array{url: string, method: string} $requestConfig
     * @param array $payload
     * @return void
     */
    private function sendWebhook(PendingRequest $pendingRequest, array $requestConfig, array $payload): void
    {
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

    /**
     * Resolves and returns the alert context as an associative array.
     *
     * @return array An array containing details about the alert, including a project, probe, reason,
     *               failure count, alert time, triggering conditions, and thresholds.
     */
    private function resolveAlertContext(): array
    {
        return [
            'project' => Arr::get($this->alertData, 'project'),
            'probe' => Arr::get($this->alertData, 'probe'),
            'reason' => Arr::get($this->alertData, 'reason'),
            'count' => Arr::get($this->alertData, 'count'),
            'probe_tls_expiration_date' => Arr::get($this->alertData, 'probe_tls_expiration_date'),
            'alert_time' => now()->toDateTimeString(),
            'trigger_after_failures' => Arr::get($this->alertData, 'alert.trigger_after_failures'),
            'cooldown_minutes' => Arr::get($this->alertData, 'alert.cooldown_minutes'),
            'latency_threshold_ms' => Arr::get($this->alertData, 'alert.latency_threshold_ms'),
        ];
    }

    /**
     * Resolves the mail configuration settings.
     *
     * @param UplinkrConfig $config The configuration instance containing mail-related settings.
     * @return array An associative array of mail configuration values including mailer, subject prefix, from address, and from name.
     */
    private function resolveMailConfig(UplinkrConfig $config): array
    {
        return [
            'mailer' => $config->getMailMailer(),
            'subject_prefix' => $config->getMailSubjectPrefix(),
            'from_address' => $config->getMailFromAddress(),
            'from_name' => $config->getMailFromName(),
        ];
    }

    /**
     * Builds a mail message for a project alert notification.
     *
     * @param array $context An array containing the context of the alert, including project information, probe details, failure counts, and alert timings.
     * @param array $mailConfig An array containing mail configuration details, such as subject prefix and other mail-related settings.
     * @return MailMessage The constructed mail message containing the alert's details and configuration.
     */
    private function buildMailMessage(array $context, array $mailConfig): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('uplinkr::messages.project_alerts_mail_subject', [
                'prefix' => $mailConfig['subject_prefix'],
                'project' => $context['project'],
                'probe' => $context['probe'],
            ]))
            ->greeting(__('uplinkr::messages.project_alerts_mail_greeting', ['project' => $context['project']]))
            ->lines([
                __('uplinkr::messages.project_alerts_mail_details_head'),
                __('uplinkr::messages.project_alerts_mail_details_probe', ['probe' => $context['probe']]),
                __('uplinkr::messages.project_alerts_mail_details_reason'),
                __('uplinkr::messages.project_alerts_mail_details_failure_count', ['failureCount' => $context['count']]),
                __('uplinkr::messages.project_alerts_mail_details_alert_time', ['alertTime' => $context['alert_time']]),
                __('uplinkr::messages.project_alerts_mail_details_probe_tls_expiration_date', [
                    'probeTlsExpirationDate' => $context['probe_tls_expiration_date'] ?? 'n/a',
                ]),
                __('uplinkr::messages.project_alerts_mail_accompanying_text_head'),
                __('uplinkr::messages.project_alerts_mail_accompanying_text'),
                __('uplinkr::messages.project_alerts_mail_accompanying_text_note', ['cooldownMinutes' => $context['cooldown_minutes']]),
                __('uplinkr::messages.project_alerts_mail_alert_settings_head'),
                __('uplinkr::messages.project_alerts_mail_alert_settings_trigger_after_failures', ['triggerAfterFailures' => $context['trigger_after_failures']]),
                __('uplinkr::messages.project_alerts_mail_alert_settings_latency_threshold_ms', ['latencyThresholdMs' => $context['latency_threshold_ms']]),
                __('uplinkr::messages.project_alerts_mail_alert_settings_latency_cooldown_minutes', ['cooldownMinutes' => $context['cooldown_minutes']]),
            ]);
    }

    /**
     * Apply the mail delivery configuration to the given mail message.
     *
     * @param MailMessage $message The mail message instance to configure.
     * @param array $mailConfig An array containing mail delivery settings, including 'mailer', 'from_address', and 'from_name'.
     * @return MailMessage The configured mail message instance.
     */
    private function applyMailDeliveryConfig(MailMessage $message, array $mailConfig): MailMessage
    {
        if ($mailConfig['mailer']) {
            $message->mailer($mailConfig['mailer']);
        }

        if ($mailConfig['from_address']) {
            $message->from($mailConfig['from_address'], $mailConfig['from_name']);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return array_merge($this->alertData, [
            'probe_tls_expiration_date' => Arr::get($this->alertData, 'probe_tls_expiration_date'),
        ]);
    }
}
