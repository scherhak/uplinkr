<?php

namespace Uplinkr\Tests\Storage;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileProbeResultsStorage;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class FileProbeResultsStorageTest extends TestCase
{
    private FileProbeResultsStorage $storage;
    private UplinkrConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            probeResultsPath: 'probes',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($this->config);
        $this->storage = new FileProbeResultsStorage($this->config, $sanitizer);
    }

    public function test_save_result_creates_file_and_stores_data(): void
    {
        $resultData = [
            'settings' => [
                'url' => 'http://example.com',
                'project' => 'my-project'
            ],
            'probe_status' => 'reachable',
            'probe_message' => ['duration_ms' => 100],
            'probe_tls_expiration_date' => '2026-12-31T23:59:59+00:00',
        ];

        $this->storage->saveResult($resultData);

        $expectedFilename = 'uplinkr/my-project/probes/example_com@' . date('Y-m-d') . '.json';
        
        Storage::disk('local')->assertExists($expectedFilename);
        
        $content = Storage::disk('local')->get($expectedFilename);
        $decoded = json_decode($content, true);
        
        $this->assertCount(1, $decoded);
        $this->assertEquals($resultData, $decoded[0]);
    }

    public function test_save_result_appends_to_existing_file(): void
    {
        $result1 = [
            'settings' => ['url' => 'http://example.com', 'project' => 'my-project'],
            'probe_status' => 'reachable'
        ];
        $result2 = [
            'settings' => ['url' => 'http://example.com', 'project' => 'my-project'],
            'probe_status' => 'unreachable'
        ];

        $this->storage->saveResult($result1);
        $this->storage->saveResult($result2);

        $expectedFilename = 'uplinkr/my-project/probes/example_com@' . date('Y-m-d') . '.json';
        
        $content = Storage::disk('local')->get($expectedFilename);
        $decoded = json_decode($content, true);
        
        $this->assertCount(2, $decoded);
        $this->assertEquals($result1, $decoded[0]);
        $this->assertEquals($result2, $decoded[1]);
    }

    public function test_save_result_uses_standard_project_if_unknown(): void
    {
        $resultData = [
            'settings' => [
                'url' => 'http://example.com',
                'project' => 'unknown'
            ]
        ];

        $this->storage->saveResult($resultData);

        $expectedFilename = 'uplinkr/' . $this->config->getStandardProject() . '/probes/example_com@' . date('Y-m-d') . '.json';
        
        Storage::disk('local')->assertExists($expectedFilename);
    }
}
