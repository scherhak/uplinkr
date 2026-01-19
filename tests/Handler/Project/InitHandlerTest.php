<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Project\InitHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class InitHandlerTest extends TestCase
{
    public function test_handle_creates_new_project(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $config = new UplinkrConfig();
        $options = [
            'project' => 'new-project',
            'label' => 'New Project Label',
            'description' => 'Project Description'
        ];

        $storageMock->shouldReceive('findProject')
            ->with('new-project')
            ->andReturnNull();

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) use ($options, $config) {
                return $data['project'] === $options['project'] &&
                       $data['label'] === $options['label'] &&
                       $data['description'] === $options['description'] &&
                       $data['status'] === $config->getStandardProjectStatus() &&
                       isset($data['created_at']) &&
                       isset($data['updated_at']) &&
                       is_array($data['probes']) &&
                       empty($data['probes']);
            }));

        $handler = new InitHandler($config, $storageMock);
        $result = $handler->handle($options);

        $this->assertTrue($result);
    }

    public function test_handle_updates_existing_project_preserving_probes_and_created_at(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $config = new UplinkrConfig();
        $projectName = 'existing-project';
        $createdAt = '2023-01-01 10:00:00';
        $existingProbes = [['url' => 'http://test.com']];
        
        $existingProject = [
            'project' => $projectName,
            'label' => 'Old Label',
            'description' => 'Old Description',
            'created_at' => $createdAt,
            'probes' => $existingProbes,
            'status' => 'enabled'
        ];

        $options = [
            'project' => $projectName,
            'label' => 'New Label',
            'description' => 'New Description'
        ];

        $storageMock->shouldReceive('findProject')
            ->with($projectName)
            ->andReturn($existingProject);

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) use ($options, $createdAt, $existingProbes) {
                return $data['project'] === $options['project'] &&
                       $data['label'] === $options['label'] &&
                       $data['description'] === $options['description'] &&
                       $data['created_at'] === $createdAt &&
                       $data['probes'] === $existingProbes;
            }));

        $handler = new InitHandler($config, $storageMock);
        $result = $handler->handle($options);

        $this->assertTrue($result);
    }
}
