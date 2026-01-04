<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Project\EnableHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class EnableHandlerTest extends TestCase
{
    public function test_handle_sets_status_to_enabled(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $projectName = 'test-project';
        $existingData = [
            'project' => $projectName,
            'status' => 'disabled',
            'updated_at' => '2023-01-01 00:00:00',
        ];

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($existingData);

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) use ($projectName) {
                return $data['project'] === $projectName &&
                    $data['status'] === 'enabled' &&
                    isset($data['updated_at']) &&
                    $data['updated_at'] !== '2023-01-01 00:00:00';
            }));

        $handler = new EnableHandler($storageMock);
        $result = $handler->handle($projectName);

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

        $handler = new EnableHandler($storageMock);
        $result = $handler->handle($projectName);

        $this->assertFalse($result);
    }
}
