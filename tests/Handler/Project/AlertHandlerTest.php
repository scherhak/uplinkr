<?php

namespace Uplinkr\Tests\Handler\Project;

use Mockery;
use Uplinkr\Handler\Project\AlertHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Tests\TestCase;

class AlertHandlerTest extends TestCase
{
    public function test_handle_updates_alert_settings(): void
    {
        $storageMock = Mockery::mock(ProjectStorageInterface::class);
        $projectName = 'test-project';
        $existingData = [
            'project' => $projectName,
            'alerts' => [],
            'updated_at' => '2023-01-01 00:00:00',
        ];

        $options = [
            'project' => $projectName,
            'enabled' => true,
            'trigger_after_failures' => 5,
            'cooldown_minutes' => 60,
            'latency_threshold_ms' => 2000,
            'trigger_after_slow' => 5,
            'channels' => ['mail', 'slack'],
        ];

        $storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($existingData);

        $storageMock->shouldReceive('saveProject')
            ->once()
            ->with(Mockery::on(function ($data) use ($projectName) {
                return $data['project'] === $projectName &&
                       count($data['alerts']) === 1 &&
                       $data['alerts'][0]['enabled'] === true &&
                       $data['alerts'][0]['trigger_after_failures'] === 5 &&
                       $data['alerts'][0]['cooldown_minutes'] === 60 &&
                       $data['alerts'][0]['latency_threshold_ms'] === 2000 &&
                       $data['alerts'][0]['trigger_after_slow'] === 5 &&
                       $data['alerts'][0]['channels'] === ['mail', 'slack'] &&
                       isset($data['updated_at']);
            }));

        $handler = new AlertHandler($storageMock);
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

        $handler = new AlertHandler($storageMock);
        $result = $handler->handle(['project' => $projectName]);

        $this->assertFalse($result);
    }
}
