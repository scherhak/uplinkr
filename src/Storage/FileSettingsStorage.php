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
     * @return array{enabled: bool, interval_hours: int, channels: array}
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

        $settings['iam_alive'] = [
            'enabled' => $enabled ?? Arr::get($current, 'enabled', false),
            'interval_hours' => $intervalHours ?? Arr::get($current, 'interval_hours', 24),
            'channels' => $channels ?? Arr::get($current, 'channels', ['mail']),
        ];

        $this->saveSettings($settings);

        return $settings['iam_alive'];
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
