<?php

namespace Uplinkr\Objects\Config;

/**
 * @package Uplinkr\Objects\Config
 *
 * Configuration value object for Uplinkr application settings.
 * Provides type-safe access to configuration values with centralized defaults.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
final class UplinkrConfig
{
    /**
     * Constructor.
     *
     * @param string $storageDisk Storage disk name
     * @param string $storagePath Base storage path
     * @param string $probeResultsPath Probe results subdirectory
     * @param int $standardLatency Standard latency threshold in milliseconds
     * @param string $probeFilenameSeparator Separator for probe filenames
     * @param string $fileExtension Default file extension
     * @param bool $prettyPrintProbeResults Whether probe result JSON should be pretty-printed
     * @param string $archivedFolder Archived projects folder name
     * @param bool $allowCompleteWipe Allow complete wipe of data
     * @param string $probeResultsGrouping How to group probe results (hourly, daily, monthly)
     * @param string $probeExecutionMode Probe execution mode (direct, job)
     * @param string $probeQueueConnection Queue connection name for job execution
     * @param string $standardProject Default project name
     * @param string $standardProjectStatus Default project status
     * @param string|null $mailMailer Mail mailer name
     * @param string $mailSubjectPrefix Mail subject prefix
     * @param string|null $mailFromAddress Mail from address
     * @param string|null $mailFromName Mail from name
     * @param bool $webhookEnabled Whether webhook notifications are enabled
     * @param string|null $webhookUrl Webhook URL
     * @param string $webhookMethod Webhook HTTP method
     * @param int $webhookTimeoutSeconds Webhook timeout in seconds
     * @param int $webhookConnectTimeoutSeconds Webhook connection timeout in seconds
     * @param bool $webhookVerifyTls Whether to verify TLS for webhooks
     * @param array $webhookHeaders Webhook headers
     * @param array $webhookRetry Webhook retry configuration
     * @param array $webhookSigning Webhook signing configuration
     * @param string|null $payloadVersion Payload version identifier
     * @param array $mailTo Mail recipients
     * @param string $logChannel Log channel name
     * @param array $logDefinition Log configuration
     * @param string $userAgent User-Agent string for HTTP probes
     */
    public function __construct(
        public string  $storageDisk = 'local',
        public string  $storagePath = 'uplinkr',
        public string  $probeResultsPath = 'probes',
        public int     $standardLatency = 1500,
        public string  $probeFilenameSeparator = '@',
        public string  $fileExtension = 'json',
        public bool    $prettyPrintProbeResults = true,
        public string  $archivedFolder = 'archived',
        public bool    $allowCompleteWipe = false,
        public string  $probeResultsGrouping = 'daily',
        public string  $probeExecutionMode = 'direct',
        public string  $probeQueueConnection = 'sync',
        public string  $standardProject = 'standard_project',
        public string  $standardProjectStatus = 'enabled',
        public ?string $mailMailer = null,
        public string  $mailSubjectPrefix = '[Uplinkr]',
        public ?string $mailFromAddress = null,
        public ?string $mailFromName = null,
        public bool    $webhookEnabled = false,
        public ?string $webhookUrl = null,
        public string  $webhookMethod = 'POST',
        public int     $webhookTimeoutSeconds = 10,
        public int     $webhookConnectTimeoutSeconds = 5,
        public bool    $webhookVerifyTls = true,
        public array   $webhookHeaders = ['Content-Type' => 'application/json'],
        public array   $webhookRetry = ['max_attempts' => 3, 'backoff_ms' => [0, 2000, 10000]],
        public array   $webhookSigning = ['enabled' => false, 'header' => 'X-Uplinkr-Signature', 'algo' => 'sha256'],
        public ?string $payloadVersion = 'uplinkr.v1',
        public array   $mailTo = [],
        public string  $logChannel = 'uplinkr',
        public array   $logDefinition = [],
        public string  $userAgent = 'uplinkr-monitor',
    )
    {
    }

    /**
     * Creates an instance from Laravel's config repository.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        return new self(
            storageDisk: config('uplinkr.storage.disk', 'local'),
            storagePath: config('uplinkr.storage.path', 'uplinkr'),
            probeResultsPath: config('uplinkr.storage.probe_results', 'probes'),
            standardLatency: config('uplinkr.probes.standard_latency', 1500),
            probeFilenameSeparator: config('uplinkr.storage.probe_filename_separator', '@'),
            fileExtension: config('uplinkr.storage.file_extension', 'json'),
            prettyPrintProbeResults: config('uplinkr.storage.pretty_print_probe_results', true),
            archivedFolder: config('uplinkr.storage.archive_folder', 'archived'),
            allowCompleteWipe: config('uplinkr.storage.allow_complete_wipe', false),
            probeResultsGrouping: config('uplinkr.storage.probe_results_grouping', 'daily'),
            probeExecutionMode: config('uplinkr.probes.execution_mode', 'direct'),
            probeQueueConnection: config('uplinkr.probes.queue_connection', 'sync'),
            standardProject: config('uplinkr.projects.standard_project', 'standard_project'),
            standardProjectStatus: config('uplinkr.projects.standard_project_status', 'enabled'),
            mailMailer: config('uplinkr.notifications.channels.mail.mailer'),
            mailSubjectPrefix: config('uplinkr.notifications.channels.mail.subject_prefix', '[Uplinkr]'),
            mailFromAddress: config('uplinkr.notifications.channels.mail.from.address'),
            mailFromName: config('uplinkr.notifications.channels.mail.from.name'),
            webhookEnabled: config('uplinkr.notifications.channels.webhook.enabled', false),
            webhookUrl: config('uplinkr.notifications.channels.webhook.url'),
            webhookMethod: config('uplinkr.notifications.channels.webhook.method', 'POST'),
            webhookTimeoutSeconds: config('uplinkr.notifications.channels.webhook.timeout_seconds', 10),
            webhookConnectTimeoutSeconds: config('uplinkr.notifications.channels.webhook.connect_timeout_seconds', 5),
            webhookVerifyTls: config('uplinkr.notifications.channels.webhook.verify_tls', true),
            webhookHeaders: config('uplinkr.notifications.channels.webhook.headers', ['Content-Type' => 'application/json']),
            webhookRetry: config('uplinkr.notifications.channels.webhook.retry', ['max_attempts' => 3, 'backoff_ms' => [0, 2000, 10000]]),
            webhookSigning: config('uplinkr.notifications.channels.webhook.signing', ['enabled' => false, 'header' => 'X-Uplinkr-Signature', 'algo' => 'sha256']),
            payloadVersion: config('uplinkr.notifications.payload.version', 'uplinkr.v1'),
            mailTo: config('uplinkr.notifications.channels.mail.to', []),
            logChannel: config('uplinkr.log_channel', 'uplinkr'),
            logDefinition: config('uplinkr.log', []),
            userAgent: config('uplinkr.probes.user_agent', 'uplinkr-monitor'),
        );
    }

    /**
     * @return string
     */
    public function getStorageDisc(): string
    {
        return $this->storageDisk;
    }

    /**
     * Get the storage path.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * @return string
     */
    public function getProbeResultsPath(): string
    {
        return $this->probeResultsPath;
    }

    /**
     * Get the standard project name.
     *
     * @return string
     */
    public function getStandardProject(): string
    {
        return $this->standardProject;
    }

    /**
     * @return string
     */
    public function getStandardProjectStatus(): string
    {
        return $this->standardProjectStatus;
    }

    /**
     * Get the file extension for storage files.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * @return string
     */
    public function getProbeFilenameSeparator(): string
    {
        return $this->probeFilenameSeparator;
    }

    /**
     * @return bool
     */
    public function shouldPrettyPrintProbeResults(): bool
    {
        return $this->prettyPrintProbeResults;
    }

    /**
     * @return string
     */
    public function getArchivedFolder(): string
    {
        return $this->archivedFolder;
    }

    /**
     * @return bool
     */
    public function allowCompleteWipe(): bool
    {
        return $this->allowCompleteWipe;
    }

    /**
     * @return string|null
     */
    public function getMailMailer(): ?string
    {
        return $this->mailMailer;
    }

    /**
     * @return string
     */
    public function getMailSubjectPrefix(): string
    {
        return $this->mailSubjectPrefix;
    }

    /**
     * @return string|null
     */
    public function getMailFromAddress(): ?string
    {
        return $this->mailFromAddress;
    }

    /**
     * @return string|null
     */
    public function getMailFromName(): ?string
    {
        return $this->mailFromName;
    }

    /**
     * @return bool
     */
    public function isWebhookEnabled(): bool
    {
        return $this->webhookEnabled;
    }

    /**
     * @return string|null
     */
    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    /**
     * @return string
     */
    public function getWebhookMethod(): string
    {
        return $this->webhookMethod;
    }

    /**
     * @return int
     */
    public function getWebhookTimeoutSeconds(): int
    {
        return $this->webhookTimeoutSeconds;
    }

    /**
     * @return int
     */
    public function getWebhookConnectTimeoutSeconds(): int
    {
        return $this->webhookConnectTimeoutSeconds;
    }

    /**
     * @return bool
     */
    public function isWebhookVerifyTls(): bool
    {
        return $this->webhookVerifyTls;
    }

    /**
     * @return array
     */
    public function getWebhookHeaders(): array
    {
        return $this->webhookHeaders;
    }

    /**
     * @return array
     */
    public function getWebhookRetry(): array
    {
        return $this->webhookRetry;
    }

    /**
     * @return array
     */
    public function getWebhookSigning(): array
    {
        return $this->webhookSigning;
    }

    /**
     * @return string|null
     */
    public function getPayloadVersion(): ?string
    {
        return $this->payloadVersion;
    }

    /**
     * @return array
     */
    public function getMailTo(): array
    {
        return $this->mailTo;
    }

    /**
     * @return string
     */
    public function getLogChannel(): string
    {
        return $this->logChannel;
    }

    /**
     * @return array
     */
    public function getLogDefinition(): array
    {
        return $this->logDefinition;
    }

    /**
     * Get the probe results grouping strategy.
     *
     * @return string
     */
    public function getProbeResultsGrouping(): string
    {
        return $this->probeResultsGrouping;
    }

    /**
     * Get the date format string based on the configured grouping strategy.
     *
     * @return string Date format compatible with PHP's date() function
     */
    public function getProbeResultsDateFormat(): string
    {
        return match ($this->probeResultsGrouping) {
            'hourly' => 'Y-m-d-H',
            'monthly' => 'Y-m',
            default => 'Y-m-d',
        };
    }

    /**
     * Get the regex pattern for extracting dates from filenames based on grouping strategy.
     *
     * @return string Regex pattern
     */
    public function getProbeResultsDatePattern(): string
    {
        return match ($this->probeResultsGrouping) {
            'hourly' => '/(\d{4}-\d{2}-\d{2}-\d{2})/',
            'daily' => '/(\d{4}-\d{2}-\d{2})/',
            'monthly' => '/(\d{4}-\d{2})/',
            default => '/(\d{4}-\d{2}-\d{2})/',
        };
    }

    /**
     * Get the Carbon parse format for the configured grouping strategy.
     *
     * @return string Format string for Carbon::createFromFormat()
     */
    public function getProbeResultsCarbonFormat(): string
    {
        return match ($this->probeResultsGrouping) {
            'hourly' => 'Y-m-d-H',
            'daily' => 'Y-m-d',
            'monthly' => 'Y-m',
            default => 'Y-m-d',
        };
    }

    /**
     * Get the User-Agent string for HTTP probes.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Get the probe execution mode.
     *
     * @return string
     */
    public function getProbeExecutionMode(): string
    {
        return $this->probeExecutionMode;
    }

    /**
     * Get the probe queue connection name.
     *
     * @return string
     */
    public function getProbeQueueConnection(): string
    {
        return $this->probeQueueConnection;
    }

    /**
     * Check if probes should be executed as jobs.
     *
     * @return bool
     */
    public function shouldExecuteProbesAsJob(): bool
    {
        return $this->probeExecutionMode === 'job';
    }
}
