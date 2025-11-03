<?php

namespace Uplinkr\Tests\Console;

use Illuminate\Support\Facades\Http;
use Uplinkr\Tests\TestCase;

class ProbeUrlCommandTest extends TestCase
{
    /** @test */
    public function it_checks_a_url_and_outputs_status_and_latency()
    {
        Http::fake([
            'https://example.org/*' => Http::response('', 200),
        ]);

        $this->artisan('uplinkr:check https://example.org --timeout=3')
            ->expectsOutputToContain('https://example.org')
            ->expectsOutputToContain('200')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_unreachable_hosts_gracefully()
    {
        Http::fake([
            'https://offline.test/*' => Http::response('', 500),
        ]);

        $this->artisan('uplinkr:check https://offline.test')
            ->expectsOutputToContain('offline.test')
            ->assertExitCode(1);
    }
}
