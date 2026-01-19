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
    public function __construct(
        public string $storageDisk = 'local',
        public string $storagePath = 'uplinkr',
        public string $probeResultsPath = 'probes',
        public int    $standardLatency = 1500,
        public string $probeFilenameSeparator = '@',
        public string $fileExtension = 'json',
        public string $archivedFolder = 'archived',
        public bool   $allowCompleteWipe = false,
        public string $standardProject = 'standard_project',
        public string $standardProjectStatus = 'enabled',
        public ?string $mailMailer = null,
        public string $mailSubjectPrefix = '[Uplinkr]',
        public ?string $mailFromAddress = null,
        public ?string $mailFromName = null,
        public bool   $webhookEnabled = false,
        public ?string $webhookUrl = null,
        public string $webhookMethod = 'POST',
        public int    $webhookTimeoutSeconds = 10,
        public int    $webhookConnectTimeoutSeconds = 5,
        public bool   $webhookVerifyTls = true,
        public array  $webhookHeaders = ['Content-Type' => 'application/json'],
        public array  $webhookRetry = ['max_attempts' => 3, 'backoff_ms' => [0, 2000, 10000]],
        public array  $webhookSigning = ['enabled' => false, 'header' => 'X-Uplinkr-Signature', 'algo' => 'sha256'],
        public ?string $payloadVersion = 'uplinkr.v1',
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
            archivedFolder: config('uplinkr.storage.archive_folder', 'archived'),
            allowCompleteWipe: config('uplinkr.storage.allow_complete_wipe', false),
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
}