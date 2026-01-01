<?php

namespace Uplinkr\Handler\Project;

use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Objects\Project\ProjectValues;

/**
 * Class InitHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class InitHandler
{
    /**
     * Constructor method for initializing the class with a project storage instance.
     *
     * @param ProjectStorageInterface $projectStorage The project storage implementation.
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config,
        private readonly ProjectStorageInterface $projectStorage
    )
    {
    }

    /**
     * Creates and saves a new project with the provided options.
     *
     * @param array $options An associative array containing project details such as 'project', 'label', and 'description'.
     * @return bool
     * @throws JsonException
     */
    public function handle(array $options): bool
    {
        $optionsValues = new ProjectValues($options);
        $projectName = $optionsValues->getName();
        $existingProject = $this->projectStorage->findProject($projectName);
        $projectValues = $existingProject ? new ProjectValues($existingProject) : null;

        $data = [
            'project' => $projectName,
            'label' => $optionsValues->getLabel(),
            'description' => $optionsValues->getDescription(),
            'created_at' => ($projectValues?->getCreatedAt()) ?? now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'status' => $this->config->getStandardProjectStatus(),
            'probes' => $projectValues ? $projectValues->getProbes() : [],
            'alerts' => [],
        ];

        $this->projectStorage->saveProject($data);

        return true;
    }

}