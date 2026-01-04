<?php

namespace Uplinkr\Handler\Project;

use Arr;
use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Project\ProjectValues;
use Uplinkr\Support\Time;

/**
 * Class UpdateHandler
 * @package Uplinkr\Handler\Project
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class UpdateHandler
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
     * Updates an existing project with the provided options, keeping 'probes' untouched.
     *
     * @param array $options An associative array containing project details such as 'project', 'label', and 'description'.
     * @return bool
     * @throws JsonException
     */
    public function handle(array $options): bool
    {
        $optionsValues = new ProjectValues($options);
        $projectName = $optionsValues->getName();
        if ($projectName === 'unknown') {
            return false;
        }

        $projectData = $this->projectStorage->findProject($projectName);
        if (!$projectData) {
            return false;
        }

        if (Arr::has($options, 'label')) {
            $projectData['label'] = $optionsValues->getLabel();
        }

        if (Arr::has($options, 'description')) {
            $projectData['description'] = $optionsValues->getDescription();
        }

        $projectData['updated_at'] = Time::now();

        $this->projectStorage->saveProject($projectData);

        return true;
    }

}
