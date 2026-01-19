<?php

namespace Uplinkr\Tests\Handler\Project;

use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Handler\Project\SummaryHandler;
use Uplinkr\Objects\Summary\ProbeResultsSummary;
use Uplinkr\Support\Sanitizer;
use Uplinkr\Tests\TestCase;

class SummaryHandlerTest extends TestCase
{
    private SummaryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new SummaryHandler(new Sanitizer(new UplinkrConfig()));
    }

    public function test_summarize_probe_results_groups_by_url(): void
    {
        $data = [
            [
                'settings' => ['url' => 'http://example.com'],
                'probe_status' => 'reachable',
                'probe_message' => ['duration_ms' => 100],
                'status_header' => 200
            ],
            [
                'settings' => ['url' => 'http://example.com'],
                'probe_status' => 'unreachable',
                'probe_message' => ['duration_ms' => 0],
                'status_header' => 500
            ],
            [
                'settings' => ['url' => 'http://other.com'],
                'probe_status' => 'reachable',
                'probe_message' => ['duration_ms' => 200],
                'status_header' => 200
            ]
        ];

        $results = $this->handler->summarizeProbeResults($data);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('example_com', $results);
        $this->assertArrayHasKey('other_com', $results);

        /** @var ProbeResultsSummary $summary */
        $summary = $results['example_com'];
        $this->assertEquals('http://example.com', $summary->url);
        $this->assertEquals(2, $summary->total);
        $this->assertEquals(1, $summary->reachable);
        $this->assertEquals(1, $summary->unreachable);
        $this->assertEquals(50.0, $summary->avgDurationMs);
    }

    public function test_summarize_probe_results_handles_empty_data(): void
    {
        $results = $this->handler->summarizeProbeResults([]);
        $this->assertEmpty($results);
    }

    public function test_summarize_probe_results_filters_invalid_lines(): void
    {
        $data = [
            ['settings' => ['url' => '']], // empty url
            ['no_settings' => 'here'],    // missing settings
            'not an array'                // not an array
        ];

        $results = $this->handler->summarizeProbeResults($data);
        $this->assertEmpty($results);
    }
}
