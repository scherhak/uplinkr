<?php

namespace Uplinkr\Handler\Project;

use Arr;
use Carbon\Carbon;
use Uplinkr\Interfaces\ProjectStorageInterface;

/**
 * Class InitHandler
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
    {}

    /**
     * Creates and saves a new project with the provided options.
     *
     * @param array $options An associative array containing project details such as 'project', 'label', and 'description'.
     * @return bool
     */
    public function handle(array $options): bool
    {
        $this->projectStorage->addToProject([
            'project' => Arr::get($options, 'project'),
            'label' => Arr::get($options, 'label'),
            'description' => Arr::get($options, 'description'),
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
            'probes' => [],
        ]);

        return true;
    }

}