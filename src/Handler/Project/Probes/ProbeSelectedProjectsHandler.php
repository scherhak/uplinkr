<?php

namespace Uplinkr\Handler\Project\Probes;

use JsonException;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Traits\RunsProjectProbes;

/**
 * Class ProbeSelectedProjectsHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class ProbeSelectedProjectsHandler
{
    use RunsProjectProbes;

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

        $projectValues = new ProjectValues(data: $project);

        if ($projectValues->getStatus() === 'disabled') {
            return [];
        }

        $probes = $projectValues->getProbes();
        return $this->runProbes($probes, $projectName, $callback);
    }
}
