<?php

namespace Uplinkr\Handler\Project;

use Arr;
use JsonException;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Project\ProjectValues;

/**
 * Class ProbeSelectedProjectsHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeSelectedProjectsHandler
{
    /**
     * ProbeSelectedProjectsHandler constructor.
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
     * Executes all probes for a selected project.
     *
     * @param string $projectName
     * @param callable|null $callback Optional callback for each result.
     * @return array|null Returns an array of results or null if project not found.
     * @throws JsonException
     */
    public function handle(string $projectName, ?callable $callback = null): ?array
    {
        $project = $this->projectStorage->findProject($projectName);

        if (!$project) {
            return null;
        }

        $projectValues = new ProjectValues($project);
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
