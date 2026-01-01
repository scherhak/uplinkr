<?php

namespace Uplinkr\Handler\Project;

use Uplinkr\Interfaces\ProjectStorageInterface;

/**
 * Class DisableHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class DisableHandler
{
    /**
     * DisableHandler constructor.
     *
     * @param ProjectStorageInterface $projectStorage
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage
    )
    {
    }

    /**
     * Disables the specified project by setting its status to 'disabled'.
     *
     * @param string $projectName The name of the project to disable.
     * @return bool Returns true if the project was found and updated, false otherwise.
     * @throws \JsonException
     */
    public function handle(string $projectName): bool
    {
        $projectData = $this->projectStorage->findProject($projectName);

        if (!$projectData) {
            return false;
        }

        $projectData['status'] = 'disabled';
        $projectData['updated_at'] = now()->toDateTimeString();

        $this->projectStorage->saveProject($projectData);

        return true;
    }
}
