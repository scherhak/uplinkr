<?php

namespace Uplinkr\Handler\Project\Alerts;

use Exception;
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
        $projectName = Arr::get($this->alertData, 'project');
        $probeName = Arr::get($this->alertData, 'probe');
        $failureCount = Arr::get($this->alertData, 'count');
        $alertTime = now()->toDateTimeString();
        $triggerAfterFailures = Arr::get($this->alertData, 'alert.trigger_after_failures');
        $cooldownMinutes = Arr::get($this->alertData, 'alert.cooldown_minutes');
        $latencyThresholdMs = Arr::get($this->alertData, 'alert.latency_threshold_ms');

        $config = UplinkrConfig::fromConfig();
        $mailer = $config->getMailMailer();
        $subjectPrefix = $config->getMailSubjectPrefix();
        $fromAddress = $config->getMailFromAddress();
        $fromName = $config->getMailFromName();

        $message = (new MailMessage)
            ->error()
            ->subject(__('uplinkr::messages.project_alerts_mail_subject', ['prefix' => $subjectPrefix, 'project' => $projectName, 'probe' => $probeName]))
            ->greeting(__('uplinkr::messages.project_alerts_mail_greeting', ['project' => $projectName]))
            ->lines([
                __('uplinkr::messages.project_alerts_mail_details_head'),
                __('uplinkr::messages.project_alerts_mail_details_probe', ['probe' => $probeName]),
                __('uplinkr::messages.project_alerts_mail_details_reason'),
                __('uplinkr::messages.project_alerts_mail_details_failure_count', ['failureCount' => $failureCount]),
                __('uplinkr::messages.project_alerts_mail_details_alert_time', ['alertTime' => $alertTime]),
                __('uplinkr::messages.project_alerts_mail_accompanying_text_head'),
                __('uplinkr::messages.project_alerts_mail_accompanying_text'),
                __('uplinkr::messages.project_alerts_mail_accompanying_text_note', ['cooldownMinutes' => $cooldownMinutes]),
                __('uplinkr::messages.project_alerts_mail_alert_settings_head'),
                __('uplinkr::messages.project_alerts_mail_alert_settings_trigger_after_failures', ['triggerAfterFailures' => $triggerAfterFailures]),
                __('uplinkr::messages.project_alerts_mail_alert_settings_latency_threshold_ms', ['latencyThresholdMs' => $latencyThresholdMs]),
            ]);

        if ($mailer) {
            $message->mailer($mailer);
        }

        if ($fromAddress) {
            $message->from($fromAddress, $fromName);
        }

        return $message;
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
        $config = UplinkrConfig::fromConfig();

        if (!$config->isWebhookEnabled()) {
            return;
        }

        $url = $config->getWebhookUrl();
        if (!$url) {
            Logger::log()->error('Webhook URL is not configured in uplinkr.php');
            return;
        }

        $method = strtoupper($config->getWebhookMethod());
        $timeout = $config->getWebhookTimeoutSeconds();
        $connectTimeout = $config->getWebhookConnectTimeoutSeconds();
        $verifyTls = $config->isWebhookVerifyTls();
        $headers = $config->getWebhookHeaders();
        $payload = $this->toArray($notifiable);

        // Version the payload if configured
        $version = $config->getPayloadVersion();
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
        $retryConfig = $config->getWebhookRetry();
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
        $signingConfig = $config->getWebhookSigning();
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
        } catch (Exception $e) {
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