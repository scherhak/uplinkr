<?php

namespace Uplinkr\Tests\Handler\Project\Alerts;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
        Storage::fake('local');
        Notification::fake();
    }

    private function mockProject(string $projectName, array $settings): void
    {
        $this->storageMock->shouldReceive('findProject')
            ->with($projectName)
            ->andReturn($settings);
    }

    private function mockState(string $projectName, array $state): void
    {
        $projectDir = "uplinkr/$projectName";
        Storage::disk('local')->put("$projectDir/state.json", json_encode($state));
    }

    private function getUpdatedState(string $projectName): array
    {
        return json_decode(Storage::disk('local')->get("uplinkr/$projectName/state.json"), true);
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
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [
                ['enabled' => true, 'trigger_after_failures' => 3, 'channels' => ['mail']]
            ]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => [
                'GET https://example.com' => ['consecutive_failures' => 3, 'consecutive_slow' => 0]
            ]
        ]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(1, $result);
        $this->assertEquals($projectName, $result[0]['project']);
        $this->assertEquals('GET https://example.com', $result[0]['probe']);

        $updatedState = $this->getUpdatedState($projectName);
        $this->assertEquals(3, $updatedState['probes']['GET https://example.com']['total_failures']);
        $this->assertEquals(0, $updatedState['probes']['GET https://example.com']['consecutive_failures']);
        $this->assertNotNull($updatedState['probes']['GET https://example.com']['last_notified_failure_at']);
    }

    public function test_it_takes_consecutive_failures_if_total_failures_not_present(): void
    {
        Log::shouldReceive('warning')->once();

        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [['enabled' => true, 'trigger_after_failures' => 5, 'channels' => ['mail']]]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => ['GET https://example.com' => ['consecutive_failures' => 7]]
        ]);

        $this->handler->handle($projectName);

        $updatedState = $this->getUpdatedState($projectName);
        $this->assertEquals(7, $updatedState['probes']['GET https://example.com']['total_failures']);
        $this->assertEquals(0, $updatedState['probes']['GET https://example.com']['consecutive_failures']);
    }

    public function test_it_increments_total_failures_if_already_exists(): void
    {
        Log::shouldReceive('warning')->once();

        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [['enabled' => true, 'trigger_after_failures' => 5, 'channels' => ['mail']]]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => ['GET https://example.com' => ['consecutive_failures' => 5, 'total_failures' => 10]]
        ]);

        $this->handler->handle($projectName);

        $updatedState = $this->getUpdatedState($projectName);
        $this->assertEquals(15, $updatedState['probes']['GET https://example.com']['total_failures']);
        $this->assertEquals(0, $updatedState['probes']['GET https://example.com']['consecutive_failures']);
    }

    public function test_it_does_not_trigger_alert_if_not_enough_failures(): void
    {
        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [['enabled' => true, 'trigger_after_failures' => 5, 'channels' => ['mail']]]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => ['GET https://example.com' => ['consecutive_failures' => 3, 'consecutive_slow' => 0]]
        ]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }

    public function test_it_does_not_trigger_alert_if_disabled(): void
    {
        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [['enabled' => false, 'trigger_after_failures' => 1, 'channels' => ['mail']]]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => ['GET https://example.com' => ['consecutive_failures' => 5, 'consecutive_slow' => 0]]
        ]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }

    public function test_it_handles_all_projects_when_project_name_is_null(): void
    {
        $this->listHandlerMock->shouldReceive('all')
            ->once()
            ->andReturn(['storage/uplinkr/project-a', 'storage/uplinkr/project-b']);

        $this->mockProject('project-a', ['project' => 'project-a', 'alerts' => [['enabled' => true, 'trigger_after_failures' => 1]]]);
        $this->mockProject('project-b', ['project' => 'project-b', 'alerts' => [['enabled' => true, 'trigger_after_failures' => 1]]]);

        $this->mockState('project-a', ['probes' => ['probe-a' => ['consecutive_failures' => 1]]]);
        $this->mockState('project-b', ['probes' => ['probe-b' => ['consecutive_failures' => 1]]]);

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
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alarms' => [['enabled' => true, 'trigger_after_failures' => 1]]
        ]);

        $this->mockState($projectName, ['probes' => ['GET https://example.com' => ['consecutive_failures' => 1]]]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(1, $result);
        $this->assertEquals('test-project', $result[0]['project']);
    }

    public function test_it_respects_cooldown_minutes(): void
    {
        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [
                'cooldown_minutes' => 60,
                ['enabled' => true, 'trigger_after_failures' => 3, 'channels' => ['mail']]
            ]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 3,
                    'last_notified_failure_at' => now()->subMinutes(30)->toDateTimeString(),
                ]
            ]
        ]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }

    public function test_it_correctly_finds_cooldown_minutes_in_alerts(): void
    {
        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [
                'cooldown_minutes' => 60,
                ['enabled' => true, 'trigger_after_failures' => 3, 'channels' => ['mail']]
            ]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 3,
                    'last_notified_failure_at' => now()->subMinutes(30)->toDateTimeString(),
                ]
            ]
        ]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }

    public function test_it_defaults_cooldown_to_null(): void
    {
        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [
                'alarms' => ['cooldown_minutes' => 60], // Incorrect key for fallback
                ['enabled' => true, 'trigger_after_failures' => 1]
            ]
        ]);

        $this->mockState($projectName, [
            'probes' => [
                'GET https://example.com' => [
                    'consecutive_failures' => 1,
                    'last_notified_failure_at' => now()->subMinutes(10)->toDateTimeString(),
                ]
            ]
        ]);

        Log::shouldReceive('warning')->once();

        $result = $this->handler->handle($projectName);

        $this->assertCount(1, $result);
    }

    public function test_it_supports_cooldown_minutes_in_alerts(): void
    {
        Log::shouldReceive('warning')->once();

        $projectName = 'test-project';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [
                'cooldown_minutes' => 10,
                ['enabled' => true, 'trigger_after_failures' => 1]
            ]
        ]);

        $this->mockState($projectName, ['probes' => ['GET https://example.com' => ['consecutive_failures' => 1]]]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(1, $result);
        $this->assertEquals('test-project', $result[0]['project']);
    }

    public function test_it_respects_cooldown_minutes_inside_alert_object(): void
    {
        $projectName = 'uplinkr-dev-test';
        $this->mockProject($projectName, [
            'project' => $projectName,
            'alerts' => [
                ['enabled' => true, 'trigger_after_failures' => 2, 'cooldown_minutes' => 5, 'channels' => ['log']]
            ]
        ]);

        $this->mockState($projectName, [
            'project' => $projectName,
            'probes' => [
                "GET https://uplinkr.dev/fail" => [
                    'consecutive_failures' => 2,
                    'last_notified_failure_at' => now()->subMinutes(2)->toDateTimeString(),
                ]
            ]
        ]);

        $result = $this->handler->handle($projectName);

        $this->assertCount(0, $result);
    }
}
