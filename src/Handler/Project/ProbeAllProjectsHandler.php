<?php

namespace Uplinkr\Handler\Project;

use Illuminate\Support\Arr;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Project\ProjectValues;

/**
 * Class ProbeAllProjectsHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeAllProjectsHandler
{
    /**
     * ProbeAllProjectsHandler constructor.
     *
     * @param ProjectStorageInterface $projectStorage
     * @param UrlHandler $urlHandler
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage,
        public readonly UrlHandler               $urlHandler
    )
    {
    }

    /**
     * Iterates through all projects and executes their defined probes.
     *
     * @param callable|null $callback Optional callback for each result.
     * @return array Returns an array of results for each project.
     */
    public function handle(?callable $callback = null): array
    {
        $projects = $this->projectStorage->allProjects();
        $results = [];

        foreach ($projects as $project) {
            $projectValues = new ProjectValues($project);
            $projectName = $projectValues->getName();

            if ($projectValues->getStatus() === 'disabled') {
                continue;
            }

            $results[$projectName] = $this->handleProject($project, $callback);
        }

        return $results;
    }

    /**
     * Executes all probes for a single project.
     *
     * @param array $project
     * @param callable|null $callback
     * @return array
     */
    public function handleProject(array $project, ?callable $callback = null): array
    {
        $projectValues = new ProjectValues($project);
        $projectName = $projectValues->getName();
        $probes = $projectValues->getProbes();
        $results = [];

        foreach ($probes as $probe) {
            $result = $this->urlHandler->with(data: [
                'url' => Arr::get($probe, 'url'),
                'project' => $projectName,
                'method' => Arr::get($probe, 'method', 'GET'),
                'headers' => Arr::get($probe, 'headers', []),
                'body' => Arr::get($probe, 'body', '')
            ])->handle();

            $results[] = $result;

            if ($callback) {
                $callback($result, $projectName);
            }
        }

        return $results;
    }
}
