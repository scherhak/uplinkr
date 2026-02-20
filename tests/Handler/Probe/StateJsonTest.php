<?php

namespace Uplinkr\Tests\Handler\Probe;

use Illuminate\Support\Facades\Storage;
use Uplinkr\Handler\Probe\ResultHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class StateJsonTest extends TestCase
{
    private UplinkrConfig $config;
    private Sanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->config = new UplinkrConfig(storageDisk: 'local', storagePath: 'uplinkr');
        $this->sanitizer = new Sanitizer($this->config);
    }

    public function test_it_creates_state_json_when_unreachable(): void
    {
        $initialResult = [];
        $durationTime = 1.0;
        $probeMessage = ['lang_key' => 'messages.probe_unreachable'];
        $status = 'unreachable';
        $settings = [
            'url' => 'https://uplinkr.dev',
            'project' => 'uplinkr-dev',
            'method' => 'GET'
        ];

        $handler = new ResultHandler($this->config, $this->sanitizer);

        $result = $handler->with($initialResult)->build($durationTime, $probeMessage, $status, $settings);

        // check if state.json exists in the correct project directory
        // Expected path: uplinkr/uplinkr-dev/state.json
        Storage::disk('local')->assertExists('uplinkr/uplinkr-dev/state.json');

        $stateContent = json_decode(Storage::disk('local')->get('uplinkr/uplinkr-dev/state.json'), true);
        $this->assertEquals('uplinkr-dev', $stateContent['project']);
        $this->assertArrayHasKey('GET https://uplinkr.dev', $stateContent['probes']);
    }

    public function test_it_updates_existing_state_json(): void
    {
        $project = 'uplinkr-dev';
        $projectDir = 'uplinkr/uplinkr-dev';
        $stateFile = "$projectDir/state.json";

        Storage::disk('local')->put($stateFile, json_encode([
            'project' => $project,
            'updated_at' => '2026-01-03 10:00:00',
            'probes' => [
                'GET https://uplinkr.dev' => [
                    'last_seen_executed_at' => '2026-01-03 10:00:00',
                    'consecutive_failures' => 1,
                    'consecutive_slow' => 0,
                    'last_notified_failure_at' => null,
                    'last_notified_slow_at' => null,
                ]
            ]
        ]));

        $handler = new ResultHandler($this->config, $this->sanitizer);
        $handler->with([])->build(1.0, [], 'unreachable', [
            'url' => 'https://uplinkr.dev',
            'project' => $project,
            'method' => 'GET'
        ]);

        $stateContent = json_decode(Storage::disk('local')->get($stateFile), true);
        $this->assertEquals(2, $stateContent['probes']['GET https://uplinkr.dev']['consecutive_failures']);
    }

    public function test_it_persists_probe_tls_expiration_date_in_state(): void
    {
        $handler = new ResultHandler($this->config, $this->sanitizer);
        $handler->with(['probe_tls_expiration_date' => '2027-01-01T00:00:00+00:00'])->build(
            1.0,
            ['lang_key' => 'messages.probe_unreachable'],
            'unreachable',
            [
                'url' => 'https://uplinkr.dev',
                'project' => 'uplinkr-dev',
                'method' => 'GET',
            ]
        );

        $stateContent = json_decode(Storage::disk('local')->get('uplinkr/uplinkr-dev/state.json'), true);
        $this->assertSame(
            '2027-01-01T00:00:00+00:00',
            $stateContent['probes']['GET https://uplinkr.dev']['probe_tls_expiration_date']
        );
    }
}
