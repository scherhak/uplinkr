<?php

namespace Uplinkr\Interfaces;

/**
 * Storage contract for projects (init/settings/metadata).
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
interface ProjectStorageInterface
{
    /**
     * Saves the given project data to the database or storage system.
     *
     * @param array $projectData An associative array containing the project information to be saved.
     * @return void
     */
    public function saveProject(array $projectData): void;

    public function addToProject(array $probeData): void;
}
