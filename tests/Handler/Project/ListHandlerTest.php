<?php

namespace Handler\Project;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Uplinkr\Handler\Project\ListHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class ListHandlerTest extends TestCase
{
    private UplinkrConfig $config;
    private MockInterface|Filesystem $filesystemMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr_projects',
            probeResultsPath: 'probes',
            archivedFolder: 'archive'
        );

        $this->filesystemMock = Mockery::mock(Filesystem::class);
    }

    public function testListAllReturnsDirectories(): void
    {
        $expectedDirectories = ['dir1', 'dir2'];

        Storage::shouldReceive('disk')
            ->with('local')
            ->once()
            ->andReturn($this->filesystemMock);

        $this->filesystemMock->shouldReceive('directories')
            ->with('uplinkr_projects')
            ->once()
            ->andReturn($expectedDirectories);

        $handler = new ListHandler($this->config);

        $this->assertEquals($expectedDirectories, $handler->all());
    }

    public function testGetProbesCountReturnsCountOfFiles(): void
    {
        $path = 'some/project/path';

        Storage::shouldReceive('disk')
            ->with('local')
            ->once()
            ->andReturn($this->filesystemMock);

        $files = ['file1.json', 'file2.json', 'file3.json'];

        $this->filesystemMock->shouldReceive('allFiles')
            ->with($path . '/probes')
            ->once()
            ->andReturn($files);

        $handler = new ListHandler($this->config);

        $this->assertEquals(3, $handler->countProbes($path));
    }

    public function testAllWithDetailsReturnsProjectSettingsAndStateSummary(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('uplinkr_projects/project-a/settings.json', json_encode([
            'project' => 'project-a',
            'label' => 'Project A',
            'status' => 'enabled',
            'description' => 'Demo description',
            'alerts' => [[
                'enabled' => true,
                'trigger_after_failures' => 5,
                'cooldown_minutes' => 60,
                'latency_threshold_ms' => 1000,
                'trigger_after_slow' => 10,
                'channels' => ['mail'],
            ]],
            'probes' => [[
                'method' => 'GET',
                'url' => 'https://example.com',
            ]],
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->put('uplinkr_projects/project-a/state.json', json_encode([
            'probes' => [
                'GET https://example.com' => [
                    'total_failures' => 4,
                    'last_notified_failure_at' => '2026-02-28 04:39:01',
                    'last_notified_slow_at' => null,
                ],
                'GET https://example.org' => [
                    'total_failures' => 6,
                    'last_notified_failure_at' => '2026-02-28 05:00:00',
                    'last_notified_slow_at' => null,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $handler = new ListHandler($this->config);
        $projects = $handler->allWithDetails();

        $this->assertCount(1, $projects);
        $this->assertSame('project-a', $projects[0]['project']);
        $this->assertSame('Project A', $projects[0]['label']);
        $this->assertSame('enabled', $projects[0]['status']);
        $this->assertSame('Demo description', $projects[0]['description']);
        $this->assertSame(10, $projects[0]['state']['total_failures']);
        $this->assertSame('2026-02-28 05:00:00', $projects[0]['state']['last_notification_at']);
    }

    public function testAllWithDetailsUsesConfiguredFileExtension(): void
    {
        Storage::fake('local');

        $config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr_projects',
            probeResultsPath: 'probes',
            fileExtension: 'log',
            archivedFolder: 'archive'
        );

        Storage::disk('local')->put('uplinkr_projects/project-a/settings.log', json_encode([
            'project' => 'project-a',
            'label' => 'Project A',
            'status' => 'enabled',
            'description' => 'Demo description',
            'alerts' => [],
            'probes' => [],
        ], JSON_THROW_ON_ERROR));

        Storage::disk('local')->put('uplinkr_projects/project-a/state.log', json_encode([
            'probes' => [
                'GET https://example.com' => [
                    'total_failures' => 3,
                    'last_notified_failure_at' => '2026-02-28 06:00:00',
                    'last_notified_slow_at' => null,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $handler = new ListHandler($config);
        $projects = $handler->allWithDetails();

        $this->assertCount(1, $projects);
        $this->assertSame('project-a', $projects[0]['project']);
        $this->assertSame(3, $projects[0]['state']['total_failures']);
        $this->assertSame('2026-02-28 06:00:00', $projects[0]['state']['last_notification_at']);
    }
}
