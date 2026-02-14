<?php

namespace Uplinkr\Tests\Handler\Probe;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Uplinkr\Handler\Probe\ResultHandler;
use Uplinkr\Handler\Probe\UrlHandler;
use Uplinkr\Interfaces\ProbeResultsStorageInterface;
use Uplinkr\Jobs\ProbeUrl;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

/**
 * Class ProbeUrlHandlerTest
 * @package Uplinkr\Tests\Handler
 */
class ProbeUrlHandlerTest extends TestCase
{
    private UrlHandler $probeUriHandler;

    /**
     * Prepares the test environment by setting up dependencies and configurations.
     * Initializes the UrlHandler with a mocked ProbeResultsStorageInterface and a real UplinkrConfig instance.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $storageMock = $this->createMock(ProbeResultsStorageInterface::class);

        // Use a real config instance with default values for testing
        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'json'
        );

        $sanitizer = new Sanitizer($config);
        $resultHandler = new ResultHandler($config, $sanitizer);

        $this->probeUriHandler = new UrlHandler($storageMock, $config, $sanitizer, $resultHandler);
    }

    public function test_it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UrlHandler::class, $this->probeUriHandler);
    }

    public function test_handle_executes_probe_directly_when_execution_mode_is_direct(): void
    {
        Queue::fake();
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $storageMock = $this->createMock(ProbeResultsStorageInterface::class);
        $storageMock->expects($this->once())
            ->method('saveResult');

        $config = new UplinkrConfig(
            probeExecutionMode: 'direct',
            standardLatency: 1500
        );

        $sanitizer = new Sanitizer($config);
        $resultHandler = new ResultHandler($config, $sanitizer);
        $handler = new UrlHandler($storageMock, $config, $sanitizer, $resultHandler);

        $result = $handler->with([
            'url' => 'https://example.com',
            'method' => 'GET',
            'project' => 'test-project'
        ])->handle();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('probe_status', $result);
        Queue::assertNothingPushed();
    }

    public function test_handle_dispatches_job_when_execution_mode_is_job(): void
    {
        Queue::fake();

        $storageMock = $this->createMock(ProbeResultsStorageInterface::class);
        $storageMock->expects($this->never())
            ->method('saveResult');

        $config = new UplinkrConfig(
            probeExecutionMode: 'job',
            probeQueueConnection: 'redis'
        );

        $sanitizer = new Sanitizer($config);
        $resultHandler = new ResultHandler($config, $sanitizer);
        $handler = new UrlHandler($storageMock, $config, $sanitizer, $resultHandler);

        $data = [
            'url' => 'https://example.com',
            'method' => 'GET',
            'project' => 'test-project'
        ];

        $result = $handler->with($data)->handle();

        $this->assertNull($result);
        Queue::assertPushed(ProbeUrl::class, function ($job) use ($data) {
            return $job->connection === 'redis';
        });
    }

    public function test_execute_probe_performs_http_request_and_saves_result(): void
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $storageMock = $this->createMock(ProbeResultsStorageInterface::class);
        $storageMock->expects($this->once())
            ->method('saveResult')
            ->with($this->callback(function ($result) {
                return is_array($result)
                    && isset($result['probe_status'])
                    && $result['probe_status'] === 'reachable';
            }));

        $config = new UplinkrConfig(
            standardLatency: 1500,
            userAgent: 'test-agent'
        );

        $sanitizer = new Sanitizer($config);
        $resultHandler = new ResultHandler($config, $sanitizer);
        $handler = new UrlHandler($storageMock, $config, $sanitizer, $resultHandler);

        $result = $handler->with([
            'url' => 'https://example.com',
            'method' => 'GET',
            'project' => 'test-project'
        ])->executeProbe();

        $this->assertIsArray($result);
        $this->assertEquals('reachable', $result['probe_status']);
    }
}
