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

    public function removeFromProject(array $probeData): void;

    /**
     * Retrieves all projects from storage.
     *
     * @return array An array of projects, where each project is an associative array of its settings.
     */
    public function allProjects(): array;

    /**
     * @return string
     */
    public function getStoragePath(): string;
}
