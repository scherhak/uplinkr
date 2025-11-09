<?php

namespace Uplinkr\Tests\Console;

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
            'https://scherhak.com*'      => Http::response('OK', 200),
            'https://www.scherhak.com*'  => Http::response('OK', 200),
            'http://scherhak.com*'       => Http::response('OK', 200),
            'http://www.scherhak.com*'   => Http::response('OK', 200),
        ]);

        $this->artisan('uplinkr:probe-by-uri test_project https scherhak.com --force')
            ->assertExitCode(0);
    }
}
