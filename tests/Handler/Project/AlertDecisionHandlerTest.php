<?php

namespace Uplinkr\Tests\Handler\Project\Alerts;

use Illuminate\Support\Facades\Storage;
use Mockery;
use Uplinkr\Handler\Project\Alerts\AlertDecisionHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class AlertDecisionHandlerTest extends TestCase
{
    private $storageMock;
    private $config;
    private $sanitizer;
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageMock = Mockery::mock(ProjectStorageInterface::class);
        $this->config = new UplinkrConfig();
        $this->sanitizer = new Sanitizer($this->config);
        $this->handler = new AlertDecisionHandler($this->storageMock, $this->config, $this->sanitizer);
    }

    public function test_it_returns_empty_array_if_project_not_found(): void
    {
        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with('non-existent')
            ->andReturn(null);

        $result = $this->handler->handle('non-existent');

        $this->assertEquals([], $result);
    }

    public function test_it_decides_to_trigger_alert_based_on_failures(): void
    {
        $projectName = 'test-project';
        $projectSettings = [
            'project' => $projectName,
            'alerts' => [
                [
                    'enabled' => true,
                    'trigger_after_failures' => 3,
                    'channels' => ['mail']
                ]
            ]
        ];

        $state = [
            'project' => $projectName,
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 3,
                    'consecutive_slow' => 0,
                ]
            ]
        ];

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($projectSettings);

        Storage::fake('local');
        $projectDir = 'uplinkr/test-project';
        Storage::disk('local')->put("$projectDir/state.json", json_encode($state));

        $result = $this->handler->handle($projectName);

        $this->assertCount(1, $result);
        $this->assertEquals($projectName, $result[0]['project']);
        $this->assertEquals('GET https://example.com', $result[0]['probe']);
        $this->assertEquals('consecutive_failures', $result[0]['reason']);
        $this->assertEquals(3, $result[0]['count']);
    }

    public function test_it_does_not_trigger_alert_if_not_enough_failures(): void
    {
        $projectName = 'test-project';
        $projectSettings = [
            'project' => $projectName,
            'alerts' => [
                [
                    'enabled' => true,
                    'trigger_after_failures' => 5,
                    'channels' => ['mail']
                ]
            ]
        ];

        $state = [
            'project' => $projectName,
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 3,
                    'consecutive_slow' => 0,
                ]
            ]
        ];

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($projectSettings);

        Storage::fake('local');
        $projectDir = 'uplinkr/test-project';
        Storage::disk('local')->put("$projectDir/state.json", json_encode($state));

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }

    public function test_it_does_not_trigger_alert_if_disabled(): void
    {
        $projectName = 'test-project';
        $projectSettings = [
            'project' => $projectName,
            'alerts' => [
                [
                    'enabled' => false,
                    'trigger_after_failures' => 1,
                    'channels' => ['mail']
                ]
            ]
        ];

        $state = [
            'project' => $projectName,
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 5,
                    'consecutive_slow' => 0,
                ]
            ]
        ];

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($projectSettings);

        Storage::fake('local');
        $projectDir = 'uplinkr/test-project';
        Storage::disk('local')->put("$projectDir/state.json", json_encode($state));

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }
}
