<?php

namespace Uplinkr\Objects\Summary;
final class ProbeResultsSummary
{
    public function __construct(
        public readonly int     $total,
        public readonly int     $reachable,
        public readonly int     $unreachable,
        public readonly int     $unknown,
        public readonly ?string $firstExecutedAt,
        public readonly ?string $lastExecutedAt,
        public readonly ?float  $avgDurationMs,
        public readonly array   $statusHeaderCounts, // e.g. [200 => 10, 503 => 2]
    )
    {
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'reachable' => $this->reachable,
            'unreachable' => $this->unreachable,
            'unknown' => $this->unknown,
            'first_executed_at' => $this->firstExecutedAt,
            'last_executed_at' => $this->lastExecutedAt,
            'avg_duration_ms' => $this->avgDurationMs,
            'status_header_counts' => $this->statusHeaderCounts,
        ];
    }
}
