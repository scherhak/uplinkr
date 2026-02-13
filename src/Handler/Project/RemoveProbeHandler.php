<?php

namespace Uplinkr\Handler\Project;

use Uplinkr\Interfaces\ProjectStorageInterface;

/**
 * Class RemoveProbeHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
readonly class RemoveProbeHandler
{
    /**
     * Constructor method for initializing the class with a project storage instance.
     *
     * @param ProjectStorageInterface $projectStorage The project storage implementation.
     * @return void
     */
    public function __construct(
        private readonly ProjectStorageInterface $projectStorage
    )
    {
    }

    /**
     * Removes a probe from an existing project.
     *
     * @param array $options An associative array containing probe details: 'url', 'project'.
     * @return bool
     */
    public function handle(array $options): bool
    {
        $this->projectStorage->removeFromProject($options);

        return true;
    }
}
