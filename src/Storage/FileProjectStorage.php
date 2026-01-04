<?php

namespace Uplinkr\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Support\Time;

/**
 * Class FileProjectStorage
 * @package Uplinkr\Storage
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class FileProjectStorage implements ProjectStorageInterface
{
    /**
     * Constructor method.
     *
     * @param UplinkrConfig $config Configuration instance.
     * @param Sanitizer $sanitizer Sanitizer instance.
     *
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config,
        private readonly Sanitizer     $sanitizer,
    )
    {
    }

    /**
     * Saves the project data to the configured storage.
     *
     * @param array $projectData An associative array containing project information.
     *
     * @return void
     * @throws JsonException
     */
    public function saveProject(array $projectData): void
    {
        $project = $this->extractProjectName($projectData);
        $filename = $this->buildSettingsFilename($project);

        Storage::disk($this->config->getStorageDisc())->put(
            $filename,
            json_encode($projectData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Finds and retrieves the project settings from storage.
     *
     * @param string $project The name of the project to retrieve.
     *
     * @return array|null Decoded project settings as an associative array,
     *                    or null if the project does not exist or content is empty.
     * @throws JsonException
     */
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

    /**
     * Adds or updates a probe in the specified project.
     *
     * @param array $probeData An associative array containing data about the probe.
     *                         Required keys are:
     *                         - 'project': The name of the project the probe belongs to.
     *                         - 'url': The URL of the probe.
     *                         Optional keys are:
     *                         - 'method': The HTTP method used by the probe (default is 'GET').
     *                         - 'headers': An array of headers to be sent with the probe.
     *                         - 'body': The body of the request, if applicable.
     *
     * @return void
     * @throws JsonException
     */
    public function addToProject(array $probeData): void
    {
        $projectName = Arr::get($probeData, 'project');
        if (empty($projectName)) {
            return;
        }

        $project = $this->findProject($projectName);
        if (!$project) {
            return;
        }

        $projectValues = new ProjectValues($project);
        $probes = $projectValues->getProbes();
        $found = false;

        foreach ($probes as $key => $probe) {
            if (Arr::get($probe, 'url') === Arr::get($probeData, 'url')) {
                $probes[$key] = [
                    'url' => Arr::get($probeData, 'url'),
                    'project' => $projectName,
                    'method' => Arr::get($probeData, 'method', 'GET'),
                    'header' => Arr::get($probeData, 'headers'),
                    'body' => Arr::get($probeData, 'body'),
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $probes[] = [
                'url' => Arr::get($probeData, 'url'),
                'project' => $projectName,
                'method' => Arr::get($probeData, 'method', 'GET'),
                'header' => Arr::get($probeData, 'headers'),
                'body' => Arr::get($probeData, 'body'),
            ];
        }

        // TODO (0.1.1) Check why the unit test fails here when Arr:add is used.
        $project['probes'] = $probes;
        $project['updated_at'] = Time::now();

        $this->saveProject($project);
    }

    /**
     * Removes a probe from the specified project.
     *
     * @param array $probeData An associative array containing:
     *                         - 'project': The name of the project.
     *                         - 'url': The URL of the probe to be removed.
     *
     * @return void
     * @throws JsonException
     */
    public function removeFromProject(array $probeData): void
    {
        $projectName = Arr::get($probeData, 'project');
        if (empty($projectName)) {
            return;
        }

        $project = $this->findProject($projectName);
        if (!$project) {
            return;
        }

        $projectValues = new ProjectValues($project);
        $probes = $projectValues->getProbes();
        $urlToRemove = Arr::get($probeData, 'url');

        $project['probes'] = array_values(array_filter($probes, static function ($probe) use ($urlToRemove) {
            return Arr::get($probe, 'url') !== $urlToRemove;
        }));

        $project['updated_at'] = Time::now();

        $this->saveProject($project);
    }

    /**
     * @return array
     */
    public function allProjectDirectories(): array
    {
        $disk = Storage::disk($this->config->getStorageDisc());
        $storagePath = $this->config->getStoragePath();

        if (!$disk->exists($storagePath)) {
            return [];
        }

        return $disk->directories($storagePath);
    }

    /**
     * Retrieves all projects from storage by scanning the storage directory.
     *
     * @return array An array of projects, where each project is an associative array of its settings.
     * @throws JsonException
     */
    public function allProjects(): array
    {
        $directories = $this->allProjectDirectories();
        $projects = [];

        foreach ($directories as $directory) {
            $projectName = basename($directory);
            $project = $this->findProject($projectName);

            if ($project === null) {
                $projects[] = null;
                continue;
            }

            $projects[] = $project;
        }

        return $projects;
    }

    /**
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->config->getStoragePath();
    }

    /**
     * Builds the directory path for the specified project.
     *
     * @param string $project The name of the project for which the directory path is being constructed.
     *                        The project name will be sanitized to ensure it is safe for file system operations.
     *
     * @return string Returns the constructed directory path for the project.
     */
    private function buildProjectDir(string $project): string
    {
        return sprintf(
            '%s/%s',
            $this->config->getStoragePath(),
            $this->sanitizeProjectName($project)
        );
    }

    /**
     * Constructs the filename for the settings file of a given project.
     *
     * @param string $project The name of the project for which the settings filename should be generated.
     *
     * @return string The full path to the settings file, including the directory and file extension.
     */
    private function buildSettingsFilename(string $project): string
    {
        return sprintf(
            '%s/settings.%s',
            $this->buildProjectDir($project),
            $this->getFileExtension()
        );
    }

    /**
     * Extracts the project name from the provided data array.
     *
     * @param array $data An associative array that may contain project-related information.
     *                    Expected keys are:
     *                    - 'project': The primary key for the project name.
     *                    - 'name': An alternative key for the project name if 'project' is not set.
     *
     * @return string The sanitized project name. If no valid project name is found,
     *                a default standard project name is returned.
     */
    private function extractProjectName(array $data): string
    {
        $projectValues = new ProjectValues($data);
        $project = $projectValues->getName();

        if ($project === 'unknown') {
            return $this->config->getStandardProject();
        }

        return $this->sanitizeProjectName($project);
    }

    /**
     * Sanitizes a given project name by converting it into a slug-like format.
     *
     * @param string $project The original project name that needs to be sanitized.
     *                        This name may contain spaces, uppercase letters, or special characters.
     *
     * @return string A sanitized version of the project name that is suitable for use as a slug.
     *                The sanitized name will contain only lowercase letters, numbers, hyphens, and underscores.
     *                If the input cannot be sanitized, a default value of "default" is returned.
     */
    private function sanitizeProjectName(string $project): string
    {
        if (method_exists($this->sanitizer, 'slug')) {
            return (string)$this->sanitizer->slug($project);
        }

        return preg_replace('/[^a-z0-9\-_]+/', '-', strtolower(trim($project)))
            ?: 'default';
    }

    /**
     * Retrieves the file extension from the configuration.
     *
     * @return string The file extension as defined in the configuration.
     */
    private function getFileExtension(): string
    {
        return $this->config->getFileExtension();
    }
}
