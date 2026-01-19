<?php

namespace Uplinkr\Handler\Project;

use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Support\Time;

/**
 * Class EnableHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class EnableHandler
{
    /**
     * EnableHandler constructor.
     *
     * @param ProjectStorageInterface $projectStorage
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage
    )
    {
    }

    /**
     * Enables the specified project by setting its status to 'enabled'.
     *
     * @param string $projectName The name of the project to enable.
     * @return bool Returns true if the project was found and updated, false otherwise.
     * @throws JsonException
     */
    public function handle(string $projectName): bool
    {
        $projectData = $this->projectStorage->findProject($projectName);

        if (!$projectData) {
            return false;
        }

        $projectData['status'] = 'enabled';
        $projectData['updated_at'] = Time::now();

        $this->projectStorage->saveProject($projectData);

        return true;
    }
}
