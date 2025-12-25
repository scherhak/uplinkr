<?php

namespace Uplinkr\Interfaces;

/**
 * Storage contract for projects (init/settings/metadata).
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
interface ProjectStorageInterface
{

    public function saveProject(array $projectData): void;


    public function findProject(string $project): ?array;


    public function listProjects(): array;

    public function deleteProject(string $project): void;
}
