<?php

namespace Uplinkr\Handler\Project;

use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;

class InitHandler
{
    /**
     * Constructor method.
     *
     * @param UplinkrConfig $config Configuration instance.
     * @return void
     */
    public function __construct(
        private readonly UplinkrConfig $config,
        private readonly ProjectStorageInterface $projectStorage
    )
    {}

    public function init(): void
    {
        $this->projectStorage->saveProject([
            'project' => 'scherhak-com',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'urls' => [
                'https://uplinkr.dev',
                'https://scherhak.de',
            ],
            'defaults' => [
                'timeout_ms' => 5000,
                'retries' => 0,
            ],
        ]);
    }

}