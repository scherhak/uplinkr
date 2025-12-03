<?php

namespace Uplinkr\Tests\Console\Commands;

use Illuminate\Console\Command;
use Mockery\MockInterface;
use Uplinkr\Handler\ProbeApiHandler;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Tests\TestCase;

class ProbeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Since UplinkrConfig is final, we bind a real instance into the container.
        // This prevents the Mockery error and ensures that the command can be resolved.
        $this->app->instance(UplinkrConfig::class, new UplinkrConfig());
    }

    /**
     * Testet, dass der Command fehlschlägt, wenn keine valide URL übergeben wird.
     */
    public function test_it_fails_validation_without_valid_endpoint(): void
    {
        // We mock the handler so that the dependency can be resolved,
        // even if we expect the code not to reach the call.
        $this->mock(ProbeApiHandler::class);

        // Case 1: No URL
        $this->artisan('uplinkr:probe-api')
            ->assertExitCode(Command::INVALID);

        // Case 2: Invalid URL
        $this->artisan('uplinkr:probe-api', ['--endpoint' => 'not-a-url'])
            ->assertExitCode(Command::INVALID);
    }

    /**
     * Testet den erfolgreichen Durchlauf mit --force Flag.
     */
    public function test_it_executes_successfully_with_force(): void
    {
        $endpoint = 'https://api.example.com';

        $this->mock(ProbeApiHandler::class, function (MockInterface $mock) use ($endpoint) {
            $mock->shouldReceive('with')
                ->once()
                ->withArgs(function ($data) use ($endpoint) {
                    return $data['endpoint'] === $endpoint
                        && $data['method'] === 'GET' // Default
                        && $data['headers'] === []
                        && $data['body'] === null;
                })
                ->andReturnSelf();

            $mock->shouldReceive('handle')
                ->once()
                ->andReturn(['status' => 'ok']);
        });

        $this->artisan('uplinkr:probe-api', [
            '--endpoint' => $endpoint,
            '--force' => true,
        ])->assertExitCode(Command::SUCCESS);
    }

    /**
     * Testet, dass alle Parameter korrekt an den Handler übergeben werden.
     */
    public function test_it_passes_all_arguments_to_handler(): void
    {
        $endpoint = 'https://api.example.com/v1/resource';
        $method = 'POST';
        $headers = ['Authorization: Bearer 123', 'Accept: application/json'];
        $body = '{"key":"value"}';
        $project = 'my-project';

        $this->mock(ProbeApiHandler::class, function (MockInterface $mock) use ($endpoint, $method, $headers, $body, $project) {
            $mock->shouldReceive('with')
                ->once()
                ->with([
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body,
                    'project' => $project,
                ])
                ->andReturnSelf();

            $mock->shouldReceive('handle')
                ->once()
                ->andReturn([]);
        });

        $this->artisan('uplinkr:probe-api', [
            '--endpoint' => $endpoint,
            '--method' => $method,
            '--header' => $headers,
            '--body' => $body,
            '--project' => $project,
            '--force' => true,
        ])->assertExitCode(Command::SUCCESS);
    }

    /**
     * Testet, dass der Command abbricht, wenn der User die Bestätigung verneint.
     */
    public function test_it_aborts_execution_when_confirmation_is_denied(): void
    {
        $endpoint = 'https://api.example.com';

        // Handler should NOT be called
        $this->mock(ProbeApiHandler::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('with');
            $mock->shouldNotReceive('handle');
        });

        $this->artisan('uplinkr:probe-api', ['--endpoint' => $endpoint])
            ->expectsConfirmation(__('uplinkr::messages.api_checking', [
                'endpoint' => $endpoint,
                'method' => 'GET',
            ]), 'no')
            ->assertExitCode(Command::INVALID);
    }

    /**
     * Testet, dass der Command ausgeführt wird, wenn der User bestätigt.
     */
    public function test_it_executes_when_confirmed_by_user(): void
    {
        $endpoint = 'https://api.example.com';

        $this->mock(ProbeApiHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('with')->once()->andReturnSelf();
            $mock->shouldReceive('handle')->once()->andReturn([]);
        });

        $this->artisan('uplinkr:probe-api', ['--endpoint' => $endpoint])
            ->expectsConfirmation(__('uplinkr::messages.api_checking', [
                'endpoint' => $endpoint,
                'method' => 'GET',
            ]), 'yes')
            ->assertExitCode(Command::SUCCESS);
    }
}