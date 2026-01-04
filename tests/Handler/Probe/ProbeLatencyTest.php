<?php

namespace Uplinkr\Tests\Handler\Probe;

use Illuminate\Support\Facades\Http;
use Uplinkr\Tests\TestCase;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Handler\Probe\ResultHandler;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

class ProbeLatencyTest extends TestCase
{
    private UrlHandler $urlHandler;
    private $storageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageMock = $this->createMock(ProbeResultsStorageInterface::class);

        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($config);
        $resultHandler = new ResultHandler($config, $sanitizer);

        $this->urlHandler = new UrlHandler($this->storageMock, $config, $sanitizer, $resultHandler);
    }

    public function test_it_sets_unreachable_when_latency_is_exceeded(): void
    {
        Http::fake([
            'example.com/*' => function () {
                return Http::response('OK', 200);
            },
        ]);

        $result = $this->urlHandler->with([
            'url' => 'http://example.com',
            'method' => 'GET',
            'latency' => 0 // 0ms limit should ALWAYS exceed
        ])->handle();

        $this->assertEquals('unreachable', $result['probe_status']);
    }

    public function test_it_sets_reachable_when_latency_is_not_exceeded(): void
    {
        Http::fake([
            'example.com/*' => function () {
                return Http::response('OK', 200);
            },
        ]);

        $result = $this->urlHandler->with([
            'url' => 'http://example.com',
            'method' => 'GET',
            'latency' => 5000 // 5s limit, should definitely pass
        ])->handle();

        $this->assertEquals('reachable', $result['probe_status']);
    }

    public function test_it_uses_default_latency_of_1500ms(): void
    {
        Http::fake([
            'slow.com/*' => function () {
                // We keep the slow one slow enough to exceed default 1500ms
                usleep(1600000); // 1.6s = 1600ms
                return Http::response('OK', 200);
            },
            'fast.com/*' => function () {
                // We make the fast one fast enough to be well below 1500ms
                return Http::response('OK', 200);
            },
        ]);

        // Should be unreachable (1600 > 1500)
        $resultSlow = $this->urlHandler->with([
            'url' => 'http://slow.com',
            'method' => 'GET',
        ])->handle();
        $this->assertEquals('unreachable', $resultSlow['probe_status']);

        // Should be reachable (nearly 0 < 1500)
        $resultFast = $this->urlHandler->with([
            'url' => 'http://fast.com',
            'method' => 'GET',
        ])->handle();
        $this->assertEquals('reachable', $resultFast['probe_status']);
    }

    public function test_it_handles_null_latency_by_using_default(): void
    {
        Http::fake([
            'example.com/*' => function () {
                return Http::response('OK', 200);
            },
        ]);

        // Simulating the command passing null when option is not provided but present in array
        $result = $this->urlHandler->with([
            'url' => 'http://example.com',
            'method' => 'GET',
            'latency' => null
        ])->handle();

        $this->assertEquals('reachable', $result['probe_status'], "Status should be reachable when nearly 0ms < 1500ms (default) even if latency is null");
    }
}
