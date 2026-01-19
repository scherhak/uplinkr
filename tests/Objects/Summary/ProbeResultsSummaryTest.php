<?php

namespace Uplinkr\Tests\Objects\Summary;

use Uplinkr\Objects\Summary\ProbeResultsSummary;
use Uplinkr\Tests\TestCase;

class ProbeResultsSummaryTest extends TestCase
{
    public function test_to_array(): void
    {
        $summary = new ProbeResultsSummary(
            url: 'http://example.com',
            total: 10,
            reachable: 8,
            unreachable: 1,
            error: 1,
            unknown: 0,
            firstExecutedAt: '2023-01-01 10:00:00',
            lastExecutedAt: '2023-01-01 11:00:00',
            avgDurationMs: 150.5,
            statusHeaderCounts: [200 => 8, 500 => 2]
        );

        $expected = [
            'url' => 'http://example.com',
            'total' => 10,
            'reachable' => 8,
            'unreachable' => 1,
            'error' => 1,
            'unknown' => 0,
            'first_executed_at' => '2023-01-01 10:00:00',
            'last_executed_at' => '2023-01-01 11:00:00',
            'avg_duration_ms' => 150.5,
            'status_header_counts' => [200 => 8, 500 => 2]
        ];

        $this->assertEquals($expected, $summary->toArray());
    }
}
