<?php

namespace Uplinkr\Tests\Storage;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Storage\FileProbeResultsStorage;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class FileStorageTest extends TestCase
{
    private FileProbeResultsStorage $fileStorage;
    private UplinkrConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            standardProject: 'test_project',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($this->config);
        $this->fileStorage = new FileProbeResultsStorage($this->config, $sanitizer);
    }

    public function test_it_saves_result_as_valid_json_array(): void
    {
        $result1 = ['id' => 1, 'settings' => ['url' => 'http://example.com']];
        $result2 = ['id' => 2, 'settings' => ['url' => 'http://example.com']];

        $this->fileStorage->saveResult($result1);
        $this->fileStorage->saveResult($result2);

        $files = Storage::disk('local')->allFiles();
        $this->assertNotEmpty($files);
        $filename = $files[0];

        $content = Storage::disk('local')->get($filename);
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
        $this->assertEquals(2, $decoded[1]['id']);
    }
}
