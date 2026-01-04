<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Project\UpdateHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class UpdateHandlerTest extends TestCase
{
    public function test_handle_updates_project_data(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $projectName = 'test-project';
        $existingData = [
            'project' => $projectName,
            'label' => 'Old Label',
            'description' => 'Old Description',
            'probes' => ['probe1'],
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ];

        $options = [
            'project' => $projectName,
            'label' => 'New Label',
            'description' => 'New Description',
        ];

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($existingData);

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) use ($projectName, $existingData) {
                return $data['project'] === $projectName &&
                    $data['label'] === 'New Label' &&
                    $data['description'] === 'New Description' &&
                    $data['probes'] === ['probe1'] &&
                    $data['created_at'] === $existingData['created_at'] &&
                    isset($data['updated_at']) &&
                    $data['updated_at'] !== $existingData['updated_at'];
            }));

        $handler = new UpdateHandler($storageMock);
        $result = $handler->handle($options);

        $this->assertTrue($result);
    }

    public function test_handle_returns_false_if_project_not_found(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $projectName = 'non-existent';

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn(null);

        $handler = new UpdateHandler($storageMock);
        $result = $handler->handle(['project' => $projectName]);

        $this->assertFalse($result);
    }

    public function test_handle_updates_only_provided_fields(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $projectName = 'test-project';
        $existingData = [
            'project' => $projectName,
            'label' => 'Old Label',
            'description' => 'Old Description',
            'probes' => ['probe1'],
        ];

        $options = [
            'project' => $projectName,
            'label' => 'New Label',
            // description is missing
        ];

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($existingData);

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['label'] === 'New Label' &&
                    $data['description'] === 'Old Description' &&
                    $data['probes'] === ['probe1'];
            }));

        $handler = new UpdateHandler($storageMock);
        $handler->handle($options);
    }
}
