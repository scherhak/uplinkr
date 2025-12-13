<?php

namespace Uplinkr\Objects\Summary;

/**
 * Class ProbeResultsSummary
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
final class ProbeResultsSummary
{
    /**
     * Constructor for the class.
     *
     * @param string $url The URL of the probe.
     * @param int $total The total count of items.
     * @param int $reachable The count of reachable items.
     * @param int $unreachable The count of unreachable items.
     * @param int $unknown The count of items with unknown status.
     * @param ?string $firstExecutedAt The timestamp of the first execution, or null if not applicable.
     * @param ?string $lastExecutedAt The timestamp of the last execution, or null if not applicable.
     * @param ?float $avgDurationMs The average duration in milliseconds, or null if not calculated.
     * @param array $statusHeaderCounts An associative array of status headers and their corresponding counts.
     *
     * @return void
     */
    public function __construct(
        public readonly string  $url,
        public readonly int     $total,
        public readonly int     $reachable,
        public readonly int     $unreachable,
        public readonly int     $error,
        public readonly int     $unknown,
        public readonly ?string $firstExecutedAt,
        public readonly ?string $lastExecutedAt,
        public readonly ?float  $avgDurationMs,
        public readonly array   $statusHeaderCounts,
    )
    {
    }

    /**
     * Converts the object properties to an associative array.
     *
     * @return array An array containing the object's properties:
     *               - 'url': string, the URL of the probe.
     *               - 'total': int, the total count of items.
     *               - 'reachable': int, the count of reachable items.
     *               - 'unreachable': int, the count of unreachable items.
     *               - 'error': int, the count of error items.
     *               - 'unknown': int, the count of items with unknown status.
     *               - 'first_executed_at': ?string, the timestamp of the first execution, or null if not applicable.
     *               - 'last_executed_at': ?string, the timestamp of the last execution, or null if not applicable.
     *               - 'avg_duration_ms': ?float, the average duration in milliseconds, or null if not calculated.
     *               - 'status_header_counts': array, an associative array of status headers and their corresponding counts.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'total' => $this->total,
            'reachable' => $this->reachable,
            'unreachable' => $this->unreachable,
            'error' => $this->error,
            'unknown' => $this->unknown,
            'first_executed_at' => $this->firstExecutedAt,
            'last_executed_at' => $this->lastExecutedAt,
            'avg_duration_ms' => $this->avgDurationMs,
            'status_header_counts' => $this->statusHeaderCounts,
        ];
    }
}
