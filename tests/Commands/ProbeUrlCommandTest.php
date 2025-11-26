<?php

namespace Commands;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Uplinkr\Tests\TestCase;

class ProbeUrlCommandTest extends TestCase
{
    #[Test]
    public function it_checks_a_url_and_outputs_status_and_latency(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://uplinkr.dev*' => Http::response('OK', 200),
            'https://www.uplinkr.dev*' => Http::response('OK', 200),
            'http://uplinkr.dev*' => Http::response('OK', 200),
            'http://www.uplinkr.dev*' => Http::response('OK', 200),
        ]);

        $this->artisan('uplinkr:probe-url https uplinkr.dev test_project --force')
            ->assertExitCode(0);
    }
}
