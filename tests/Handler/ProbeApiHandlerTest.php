<?php

namespace Uplinkr\Tests\Handler;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Uplinkr\Handler\ProbeApiHandler;
use Uplinkr\Interfaces\StorageInterface;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class ProbeApiHandlerTest extends TestCase
{
    private ProbeApiHandler $handler;
    private StorageInterface $storageMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Dependencies vorbereiten
        $this->storageMock = $this->createMock(StorageInterface::class);

        $config = new UplinkrConfig(
            storagePath: 'uplinkr',
            standardProject: 'standard_project',
            fileExtension: 'log'
        );

        $sanitizer = new Sanitizer($config);

        $this->handler = new ProbeApiHandler($this->storageMock, $config, $sanitizer);
    }

    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ProbeApiHandler::class, $this->handler);
    }

    public function test_handle_returns_reachable_status_on_successful_request(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['foo' => 'bar'], 200),
        ]);

        // Erwarte, dass das Ergebnis gespeichert wird
        $this->storageMock->expects($this->once())
            ->method('saveResult')
            ->with($this->callback(function ($result) {
                return $result['probe_status'] === 'reachable'
                    && $result['settings']['endpoint'] === 'https://api.example.com/users';
            }));

        $result = $this->handler->with([
            'endpoint' => 'https://api.example.com/users',
            'method' => 'GET',
            'project' => 'test_project'
        ])->handle();

        $this->assertEquals('reachable', $result['probe_status']);
        $this->assertEquals(200, $result['status_header']);
    }

    public function test_handle_returns_unreachable_status_on_error_response(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(null, 404),
        ]);

        $this->storageMock->expects($this->once())
            ->method('saveResult')
            ->with($this->callback(function ($result) {
                return $result['probe_status'] === 'unreachable';
            }));

        $result = $this->handler->with([
            'endpoint' => 'https://api.example.com/unknown',
            'method' => 'GET',
        ])->handle();

        $this->assertEquals('unreachable', $result['probe_status']);
        $this->assertEquals(404, $result['status_header']);
    }

    public function test_handle_returns_error_status_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->storageMock->expects($this->once())
            ->method('saveResult')
            ->with($this->callback(function ($result) {
                return $result['probe_status'] === 'error'
                    && str_contains($result['probe_message']['exception'], 'Connection timed out');
            }));

        $result = $this->handler->with([
            'endpoint' => 'https://timeout.com',
            'method' => 'GET',
        ])->handle();

        $this->assertEquals('error', $result['probe_status']);
    }

    public function test_handle_sends_correct_request_data_with_headers_and_body(): void
    {
        Http::fake(['*' => Http::response('OK', 201)]);

        $endpoint = 'https://api.example.com/create';
        $method = 'POST';
        $headers = ['Authorization: Bearer token123', 'X-Custom: Value'];
        $body = ['name' => 'Test User', 'email' => 'test@example.com'];

        $this->handler->with([
            'endpoint' => $endpoint,
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
        ])->handle();

        Http::assertSent(function (Request $request) use ($endpoint, $method, $body) {
            return $request->url() === $endpoint
                && $request->method() === $method
                && $request->hasHeader('Authorization', 'Bearer token123')
                && $request->hasHeader('X-Custom', 'Value')
                && $request['name'] === 'Test User' // Prüft JSON Body Decoded
                && $request->isJson();
        });
    }
}