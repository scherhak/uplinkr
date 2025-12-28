<?php

namespace Uplinkr\Handler\Project;

use Arr;
use Carbon\Carbon;
use JsonException;
use Uplinkr\Interfaces\ProjectStorageInterface;

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
    {}

    /**
     * Updates an existing project with the provided options, keeping 'probes' untouched.
     *
     * @param array $options An associative array containing project details such as 'project', 'label', and 'description'.
     * @return bool
     * @throws JsonException
     */
    public function handle(array $options): bool
    {
        $projectName = Arr::get($options, 'project');
        if (!$projectName) {
            return false;
        }

        $projectData = $this->projectStorage->findProject($projectName);
        if (!$projectData) {
            return false;
        }

        if (Arr::has($options, 'label')) {
            $projectData['label'] = Arr::get($options, 'label');
        }

        if (Arr::has($options, 'description')) {
            $projectData['description'] = Arr::get($options, 'description');
        }

        $projectData['updated_at'] = now()->toDateTimeString();

        $this->projectStorage->saveProject($projectData);

        return true;
    }

}
