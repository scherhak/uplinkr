<?php

namespace Uplinkr\Tests\Handler\Project\Alerts;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Uplinkr\Handler\Project\Alerts\AlertDecisionHandler;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Interfaces\ProjectStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class AlertDecisionHandlerTest extends TestCase
{
    private $storageMock;
    private $listHandlerMock;
    private $config;
    private $sanitizer;
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageMock = Mockery::mock(ProjectStorageInterface::class);
        $this->listHandlerMock = Mockery::mock(ListHandler::class);
        $this->config = new UplinkrConfig();
        $this->sanitizer = new Sanitizer($this->config);
        $this->handler = new AlertDecisionHandler(
            $this->storageMock,
            $this->listHandlerMock,
            $this->config,
            $this->sanitizer
        );
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
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/Alert triggered for project "test-project" on probe "GET https:\/\/example.com". Reason: consecutive_failures \(3 failures\)/'));

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

        // Check if total_failures was updated in state.json
        // New logic: if not present, take consecutive_failures (3 in this case)
        $updatedState = json_decode(Storage::disk('local')->get("$projectDir/state.json"), true);
        $this->assertEquals(3, $updatedState['probes']['GET https://example.com']['total_failures']);
        $this->assertEquals(0, $updatedState['probes']['GET https://example.com']['consecutive_failures']);
        $this->assertNotNull($updatedState['probes']['GET https://example.com']['last_notified_failure_at']);
    }

    public function test_it_takes_consecutive_failures_if_total_failures_not_present(): void
    {
        Log::shouldReceive('warning')->once();

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
                    'consecutive_failures' => 7,
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

        $this->handler->handle($projectName);

        $updatedState = json_decode(Storage::disk('local')->get("$projectDir/state.json"), true);
        $this->assertEquals(7, $updatedState['probes']['GET https://example.com']['total_failures']);
        $this->assertEquals(0, $updatedState['probes']['GET https://example.com']['consecutive_failures']);
    }

    public function test_it_increments_total_failures_if_already_exists(): void
    {
        Log::shouldReceive('warning')->once();

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
                    'consecutive_failures' => 5,
                    'total_failures' => 10,
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

        $this->handler->handle($projectName);

        $updatedState = json_decode(Storage::disk('local')->get("$projectDir/state.json"), true);
        $this->assertEquals(15, $updatedState['probes']['GET https://example.com']['total_failures']);
        $this->assertEquals(0, $updatedState['probes']['GET https://example.com']['consecutive_failures']);
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

    public function test_it_handles_all_projects_when_project_name_is_null(): void
    {
        $projectPaths = [
            'storage/uplinkr/project-a',
            'storage/uplinkr/project-b',
        ];

        $this->listHandlerMock->shouldReceive('all')
            ->once()
            ->andReturn($projectPaths);

        $projects = [
            ['project' => 'project-a', 'alerts' => [['enabled' => true, 'trigger_after_failures' => 1]]],
            ['project' => 'project-b', 'alerts' => [['enabled' => true, 'trigger_after_failures' => 1]]],
        ];

        // Expect findProject to be called for each project NAME
        $this->storageMock->shouldReceive('findProject')
            ->with('project-a')
            ->andReturn($projects[0]);
        $this->storageMock->shouldReceive('findProject')
            ->with('project-b')
            ->andReturn($projects[1]);

        Storage::fake('local');
        Storage::disk('local')->put("uplinkr/project-a/state.json", json_encode([
            'probes' => ['probe-a' => ['consecutive_failures' => 1]]
        ]));
        Storage::disk('local')->put("uplinkr/project-b/state.json", json_encode([
            'probes' => ['probe-b' => ['consecutive_failures' => 1]]
        ]));

        Log::shouldReceive('warning')->twice();

        $result = $this->handler->handle(null);

        $this->assertCount(2, $result);
        $this->assertEquals('project-a', $result[0]['project']);
        $this->assertEquals('project-b', $result[1]['project']);
    }

    public function test_it_supports_alarms_key_in_settings(): void
    {
        Log::shouldReceive('warning')->once();

        $projectName = 'test-project';
        $projectSettings = [
            'project' => $projectName,
            'alarms' => [
                [
                    'enabled' => true,
                    'trigger_after_failures' => 1,
                ]
            ]
        ];

        $state = [
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 1,
                ]
            ]
        ];

        $this->storageMock->shouldReceive('findProject')
            ->once()
            ->with($projectName)
            ->andReturn($projectSettings);

        Storage::fake('local');
        Storage::disk('local')->put("uplinkr/test-project/state.json", json_encode($state));

        $result = $this->handler->handle($projectName);

        $this->assertCount(1, $result);
        $this->assertEquals('test-project', $result[0]['project']);
    }
}
