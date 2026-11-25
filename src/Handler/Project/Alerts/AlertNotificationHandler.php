<?php

namespace Uplinkr\Handler\Project\Alerts;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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

        if (count($context['probes']) > 1) {
            Logger::log()->warning(
                sprintf(
                    'Alert triggered for project "%s" on %d probes.',
                    $context['project'],
                    count($context['probes'])
                ),
                $this->toArray($notifiable)
            );
            return;
        }

        if ($this->isCertificateReason($context['reason'])) {
            Logger::log()->warning(
                sprintf(
                    'Certificate alert for project "%s" on probe "%s". Reason: %s. Days remaining: %d. TLS expiration date: %s',
                    $context['project'],
                    $context['probe'],
                    $context['reason'],
                    $context['certificate_days_remaining'],
                    $context['probe_tls_expiration_date'] ?? 'n/a'
                ),
                $this->toArray($notifiable)
            );
            return;
        }

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

    /**
     * @param PendingRequest $pendingRequest
     * @param UplinkrConfig $config
     * @return void
     */
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
        $probes = $this->resolveProbes();
        $firstProbe = $probes[0] ?? [];

        return [
            'project' => Arr::get($this->alertData, 'project'),
            'probe' => Arr::get($firstProbe, 'probe'),
            'reason' => Arr::get($firstProbe, 'reason'),
            'count' => Arr::get($firstProbe, 'count'),
            'probe_tls_expiration_date' => Arr::get($firstProbe, 'probe_tls_expiration_date'),
            'certificate_days_remaining' => Arr::get($firstProbe, 'certificate_days_remaining'),
            'certificate_threshold_days' => Arr::get($firstProbe, 'certificate_threshold_days'),
            'probes' => $probes,
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
        $isAggregated = count($context['probes']) > 1;
        $containsCertificateAlert = $this->containsCertificateAlert($context['probes']);
        $subject = $isAggregated && $containsCertificateAlert
            ? __('uplinkr::messages.project_alerts_mail_subject_aggregated_mixed', [
                'prefix' => $mailConfig['subject_prefix'],
                'project' => $context['project'],
                'probeCount' => count($context['probes']),
            ])
            : ($isAggregated
                ? __('uplinkr::messages.project_alerts_mail_subject_aggregated', [
                    'prefix' => $mailConfig['subject_prefix'],
                    'project' => $context['project'],
                    'probeCount' => count($context['probes']),
                ])
                : ($containsCertificateAlert
                    ? __('uplinkr::messages.project_alerts_mail_subject_certificate', [
                        'prefix' => $mailConfig['subject_prefix'],
                        'project' => $context['project'],
                        'probe' => $context['probe'],
                    ])
            : __('uplinkr::messages.project_alerts_mail_subject', [
                'prefix' => $mailConfig['subject_prefix'],
                'project' => $context['project'],
                'probe' => $context['probe'],
                    ])));

        $lines = $isAggregated
            ? $this->buildAggregatedMailLines($context)
            : $this->buildSingleMailLines($context);

        return (new MailMessage)
            ->error()
            ->subject($subject)
            ->greeting(__('uplinkr::messages.project_alerts_mail_greeting', ['project' => $context['project']]))
            ->lines($lines);
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
        $probes = $this->resolveProbes();
        $data = $this->alertData;

        if (Arr::has($this->alertData, 'probes')) {
            $data['probes'] = $probes;
        }

        return array_merge($data, [
            'probe_tls_expiration_date' => Arr::get($probes[0] ?? [], 'probe_tls_expiration_date'),
        ]);
    }

    /**
     * Resolves probes from aggregated or legacy single-probe payloads.
     *
     * @return array
     */
    private function resolveProbes(): array
    {
        $probes = Arr::get($this->alertData, 'probes');
        if (is_array($probes) && !empty($probes)) {
            $probes = array_values(array_filter($probes, static fn($probe): bool => is_array($probe)));
            usort($probes, static function (array $left, array $right): int {
                return strcmp((string)Arr::get($left, 'probe', ''), (string)Arr::get($right, 'probe', ''));
            });
            return $probes;
        }

        return [[
            'probe' => Arr::get($this->alertData, 'probe'),
            'reason' => Arr::get($this->alertData, 'reason'),
            'count' => Arr::get($this->alertData, 'count'),
            'probe_tls_expiration_date' => Arr::get($this->alertData, 'probe_tls_expiration_date'),
            'certificate_days_remaining' => Arr::get($this->alertData, 'certificate_days_remaining'),
            'certificate_threshold_days' => Arr::get($this->alertData, 'certificate_threshold_days'),
        ]];
    }

    /**
     * Builds mail lines for a single-probe alert.
     *
     * @param array $context
     * @return array
     */
    private function buildSingleMailLines(array $context): array
    {
        if ($this->isCertificateReason($context['reason'])) {
            return [
                __('uplinkr::messages.project_alerts_mail_details_head'),
                __('uplinkr::messages.project_alerts_mail_details_probe', ['probe' => $context['probe']]),
                __('uplinkr::messages.project_alerts_mail_details_certificate_reason', ['reason' => $context['reason']]),
                __('uplinkr::messages.project_alerts_mail_details_alert_time', ['alertTime' => $context['alert_time']]),
                __('uplinkr::messages.project_alerts_mail_details_probe_tls_expiration_date', [
                    'probeTlsExpirationDate' => $this->formatProbeTlsExpirationDate($context['probe_tls_expiration_date'] ?? null),
                ]),
                __('uplinkr::messages.project_alerts_mail_details_certificate_days_remaining', [
                    'daysRemaining' => $context['certificate_days_remaining'],
                ]),
                __('uplinkr::messages.project_alerts_mail_details_certificate_threshold', [
                    'thresholdDays' => $context['certificate_threshold_days'] ?? 'expired',
                ]),
                __('uplinkr::messages.project_alerts_mail_certificate_action'),
            ];
        }

        return [
            __('uplinkr::messages.project_alerts_mail_details_head'),
            __('uplinkr::messages.project_alerts_mail_details_probe', ['probe' => $context['probe']]),
            __('uplinkr::messages.project_alerts_mail_details_reason'),
            __('uplinkr::messages.project_alerts_mail_details_failure_count', ['failureCount' => $context['count']]),
            __('uplinkr::messages.project_alerts_mail_details_alert_time', ['alertTime' => $context['alert_time']]),
            __('uplinkr::messages.project_alerts_mail_details_probe_tls_expiration_date', [
                'probeTlsExpirationDate' => $this->formatProbeTlsExpirationDate($context['probe_tls_expiration_date'] ?? null),
            ]),
            __('uplinkr::messages.project_alerts_mail_accompanying_text_head'),
            __('uplinkr::messages.project_alerts_mail_accompanying_text'),
            __('uplinkr::messages.project_alerts_mail_accompanying_text_note', ['cooldownMinutes' => $context['cooldown_minutes']]),
            __('uplinkr::messages.project_alerts_mail_alert_settings_head'),
            __('uplinkr::messages.project_alerts_mail_alert_settings_trigger_after_failures', ['triggerAfterFailures' => $context['trigger_after_failures']]),
            __('uplinkr::messages.project_alerts_mail_alert_settings_latency_threshold_ms', ['latencyThresholdMs' => $context['latency_threshold_ms']]),
            __('uplinkr::messages.project_alerts_mail_alert_settings_latency_cooldown_minutes', ['cooldownMinutes' => $context['cooldown_minutes']]),
        ];
    }

    /**
     * Builds mail lines for an aggregated multi-probe alert.
     *
     * @param array $context
     * @return array
     */
    private function buildAggregatedMailLines(array $context): array
    {
        $lines = [
            __('uplinkr::messages.project_alerts_mail_details_head'),
            __('uplinkr::messages.project_alerts_mail_details_project', ['project' => $context['project']]),
            __('uplinkr::messages.project_alerts_mail_details_probes_count', ['probeCount' => count($context['probes'])]),
            __('uplinkr::messages.project_alerts_mail_details_alert_time', ['alertTime' => $context['alert_time']]),
            __('uplinkr::messages.project_alerts_mail_details_probe_list_head'),
        ];

        foreach ($context['probes'] as $probe) {
            if ($this->isCertificateReason(Arr::get($probe, 'reason'))) {
                $lines[] = __('uplinkr::messages.project_alerts_mail_details_probe_aggregated_certificate', [
                    'probe' => Arr::get($probe, 'probe'),
                    'reason' => Arr::get($probe, 'reason'),
                    'daysRemaining' => Arr::get($probe, 'certificate_days_remaining'),
                    'probeTlsExpirationDate' => $this->formatProbeTlsExpirationDate(Arr::get($probe, 'probe_tls_expiration_date')),
                ]);
            } else {
                $lines[] = __('uplinkr::messages.project_alerts_mail_details_probe_aggregated', [
                    'probe' => Arr::get($probe, 'probe'),
                    'failureCount' => Arr::get($probe, 'count'),
                    'probeTlsExpirationDate' => $this->formatProbeTlsExpirationDate(Arr::get($probe, 'probe_tls_expiration_date')),
                ]);
            }
        }

        if ($this->containsCertificateAlert($context['probes'])) {
            return array_merge($lines, [
                __('uplinkr::messages.project_alerts_mail_details_reason_mixed'),
                __('uplinkr::messages.project_alerts_mail_certificate_action'),
            ]);
        }

        return array_merge($lines, [
            __('uplinkr::messages.project_alerts_mail_details_reason'),
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
     * Formats a certificate expiration timestamp for human-readable mail output.
     *
     * @param string|null $value The stored certificate expiration timestamp.
     * @return string A UTC timestamp or `n/a` when no value is available.
     */
    private function formatProbeTlsExpirationDate(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return 'n/a';
        }

        try {
            return Carbon::parse($value)
                ->setTimezone('UTC')
                ->format('Y-m-d H:i:s \U\T\C');
        } catch (Exception) {
            return $value;
        }
    }

    /**
     * Checks whether an alert reason belongs to the certificate warning flow.
     *
     * @param mixed $reason The reason value from a normalized probe decision.
     * @return bool True for approaching-expiry and expired-certificate reasons.
     */
    private function isCertificateReason(mixed $reason): bool
    {
        return in_array($reason, ['certificate_expiration', 'certificate_expired'], true);
    }

    /**
     * Determines whether an aggregated notification includes certificate data.
     *
     * @param array $probes The normalized probe decisions in the notification.
     * @return bool True when at least one probe carries a certificate reason.
     */
    private function containsCertificateAlert(array $probes): bool
    {
        foreach ($probes as $probe) {
            if (is_array($probe) && $this->isCertificateReason(Arr::get($probe, 'reason'))) {
                return true;
            }
        }

        return false;
    }
}
