<?php

namespace Uplinkr\Handler\Project;

use Uplinkr\Interfaces\ProjectStorageInterface;

/**
 * Class AddProbeHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AddProbeHandler
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
     * Adds a probe to an existing project.
     *
     * @param array $options An associative array containing probe details: 'url', 'project', 'method', 'headers', 'body'.
     * @return bool
     */
    public function handle(array $options): bool
    {
        $this->projectStorage->addToProject($options);

        return true;
    }

}