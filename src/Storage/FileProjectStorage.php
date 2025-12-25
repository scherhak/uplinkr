<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

class FileProjectStorage implements ProjectStorageInterface
{
    public function __construct(
        private readonly UplinkrConfig $config,
        private readonly Sanitizer     $sanitizer,
    ) {}

    public function saveProject(array $projectData): void
    {
        $project = $this->extractProjectName($projectData);
        $filename = $this->buildSettingsFilename($project);

        Storage::disk($this->config->getStorageDisc())->put(
            $filename,
            json_encode($projectData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    public function findProject(string $project): ?array
    {
        $filename = $this->buildSettingsFilename($project);
        $disk = Storage::disk($this->config->getStorageDisc());

        if (!$disk->exists($filename)) {
            return null;
        }

        $content = $disk->get($filename);
        if (empty($content)) {
            return null;
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function listProjects(): array
    {
        $disk = Storage::disk($this->config->getStorageDisc());
        $base = $this->config->getStoragePath();

        if (!$disk->exists($base)) {
            return [];
        }

        $directories = $disk->directories($base);
        $projects = [];

        foreach ($directories as $dir) {
            $settingsFile = $dir . '/settings.' . $this->getFileExtension();

            if (!$disk->exists($settingsFile)) {
                continue;
            }

            $content = $disk->get($settingsFile);
            if (empty($content)) {
                continue;
            }

            $projects[] = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }

        return $projects;
    }

    public function deleteProject(string $project): void
    {
        $projectDir = $this->buildProjectDir($project);
        $disk = Storage::disk($this->config->getStorageDisc());

        if ($disk->exists($projectDir)) {
            $disk->deleteDirectory($projectDir);
        }
    }

    private function buildProjectDir(string $project): string
    {
        return sprintf(
            '%s/%s',
            $this->config->getStoragePath(),
            $this->sanitizeProjectName($project)
        );
    }

    private function buildSettingsFilename(string $project): string
    {
        return sprintf(
            '%s/settings.%s',
            $this->buildProjectDir($project),
            $this->getFileExtension()
        );
    }

    private function extractProjectName(array $data): string
    {
        $project = $data['project'] ?? $data['name'] ?? null;

        if (empty($project)) {
            return $this->config->getStandardProject();
        }

        return $this->sanitizeProjectName((string) $project);
    }

    private function sanitizeProjectName(string $project): string
    {
        if (method_exists($this->sanitizer, 'slug')) {
            return (string) $this->sanitizer->slug($project);
        }

        return preg_replace('/[^a-z0-9\-_]+/', '-', strtolower(trim($project)))
            ?: 'default';
    }

    private function getFileExtension(): string
    {
        return $this->config->getFileExtension();
    }
}
