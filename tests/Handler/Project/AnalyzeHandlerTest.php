<?php

namespace Uplinkr\Tests\Handler\Project;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Handler\Project\AnalyzeHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class AnalyzeHandlerTest extends TestCase
{
    private AnalyzeHandler $handler;
    private UplinkrConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new UplinkrConfig(
            storageDisk: 'local',
            storagePath: 'uplinkr',
            standardProject: 'test_project',
            probeResultsPath: 'probes'
        );

        $this->handler = new AnalyzeHandler($this->config);
        Storage::fake('local');
    }

    public function test_read_probe_result_file_returns_content_as_string()
    {
        $path = 'uplinkr/test_project/probes/test.json';
        Storage::disk('local')->put($path, 'test content');

        $result = $this->handler->readProbeResultFile($path);

        $this->assertEquals('test content', $result);
    }

    public function test_decode_probe_results_with_json_array()
    {
        $data = [
            ['id' => 1, 'status' => 'reachable'],
            ['id' => 2, 'status' => 'unreachable']
        ];
        $json = json_encode($data);

        $result = $this->handler->decodeProbeResults($json);

        $this->assertEquals($data, $result);
    }

    public function test_decode_probe_results_with_legacy_jsonl()
    {
        $line1 = ['id' => 1, 'status' => 'reachable'];
        $line2 = ['id' => 2, 'status' => 'unreachable'];
        $jsonl = json_encode($line1) . "\n" . json_encode($line2);

        $result = $this->handler->decodeProbeResults($jsonl);

        $this->assertCount(2, $result);
        $this->assertEquals($line1, $result[0]);
        $this->assertEquals($line2, $result[1]);
    }

    public function test_decode_probe_results_with_empty_content()
    {
        $result = $this->handler->decodeProbeResults('');
        $this->assertEquals([], $result);
    }
}
