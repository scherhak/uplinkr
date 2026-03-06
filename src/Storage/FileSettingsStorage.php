<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Objects\Config\UplinkrConfig;

class FileSettingsStorage
{
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function getSettings(): array
    {
        $path = $this->buildSettingsFilename();
        $disk = Storage::disk($this->config->getStorageDisc());

        if (!$disk->exists($path)) {
            return $this->defaultSettings();
        }

        $content = $disk->get($path);
        if (trim($content) === '') {
            return $this->defaultSettings();
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return $this->defaultSettings();
        }

        return $this->mergeDefaults($decoded);
    }

    /**
     * @param array $settings
     * @return void
     * @throws JsonException
     */
    public function saveSettings(array $settings): void
    {
        $path = $this->buildSettingsFilename();
        Storage::disk($this->config->getStorageDisc())->put(
            $path,
            json_encode($settings, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    /**
     * @return array{enabled: bool, interval_hours: int, channels: array, last_sent_at: string|null}
     * @throws JsonException
     */
    public function getIamAliveSettings(): array
    {
        $settings = $this->getSettings();

        return Arr::get($settings, 'iam_alive', $this->defaultSettings()['iam_alive']);
    }

    /**
     * @param bool|null $enabled
     * @param int|null $intervalHours
     * @param array|null $channels
     * @return array
     * @throws JsonException
     */
    public function updateIamAliveSettings(?bool $enabled, ?int $intervalHours, ?array $channels): array
    {
        $settings = $this->getSettings();
        $current = Arr::get($settings, 'iam_alive', []);

        Arr::set($settings, 'iam_alive', [
            'enabled' => $enabled ?? Arr::get($current, 'enabled', false),
            'interval_hours' => $intervalHours ?? Arr::get($current, 'interval_hours', 24),
            'channels' => $channels ?? Arr::get($current, 'channels', ['mail']),
            'last_sent_at' => Arr::get($current, 'last_sent_at'),
        ]);

        $this->saveSettings($settings);

        return Arr::get($settings, 'iam_alive', []);
    }

    /**
     * @param string $sentAt
     * @return array
     * @throws JsonException
     */
    public function markIamAliveSent(string $sentAt): array
    {
        $settings = $this->getSettings();
        $current = Arr::get($settings, 'iam_alive', []);

        Arr::set($settings, 'iam_alive', [
            'enabled' => (bool)Arr::get($current, 'enabled', false),
            'interval_hours' => (int)Arr::get($current, 'interval_hours', 24),
            'channels' => Arr::get($current, 'channels', ['mail']),
            'last_sent_at' => $sentAt,
        ]);

        $this->saveSettings($settings);

        return Arr::get($settings, 'iam_alive', []);
    }

    /**
     * @return array
     */
    private function defaultSettings(): array
    {
        return [
            'iam_alive' => [
                'enabled' => false,
                'interval_hours' => 24,
                'channels' => ['mail'],
                'last_sent_at' => null,
            ],
        ];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function mergeDefaults(array $settings): array
    {
        $defaults = $this->defaultSettings();

        return array_replace_recursive($defaults, $settings);
    }

    /**
     * @return string
     */
    private function buildSettingsFilename(): string
    {
        return sprintf(
            '%s/settings.%s',
            $this->config->getStoragePath(),
            $this->config->getFileExtension()
        );
    }
}
