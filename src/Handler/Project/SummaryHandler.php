<?php

namespace Uplinkr\Handler\Project;

use Illuminate\Support\Arr;
use Uplinkr\Objects\Summary\ProbeResultsSummary;

class SummaryHandler
{

    public function summarizeProbeResults(array $decodedLines): array
    {
        $groups = collect($decodedLines)
            ->filter(static fn($line) => is_array($line))
            ->filter(static fn($line) => is_string(Arr::get($line, 'settings.url')) && trim(Arr::get($line, 'settings.url')) !== '')
            ->groupBy(static fn($line) => trim(Arr::get($line, 'settings.url')));

        $result = [];

        foreach ($groups as $url => $items) {
            $total = $items->count();

            $reachable = $items->where('probe_status', 'reachable')->count();
            $unreachable = $items->where('probe_status', 'unreachable')->count();
            $unknown = $items->where('probe_status', 'unknown')->count();

            $unknown = max($unknown, $total - $reachable - $unreachable - $items->where('probe_status', 'error')->count());

            $executed = $items
                ->pluck('executed')
                ->filter(static fn($v) => is_string($v) && $v !== '')
                ->values();

            $durations = $items
                ->map(static fn($line) => Arr::get($line, 'probe_message.duration_ms'))
                ->filter(static fn($v) => is_numeric($v))
                ->map(static fn($v) => (float)$v)
                ->values();

            $avgDurationMs = $durations->isEmpty() ? null : round($durations->avg(), 2);

            $statusHeaderCounts = $items
                ->pluck('status_header')
                ->filter(static fn($v) => is_int($v) || ctype_digit((string)$v))
                ->map(static fn($v) => (int)$v)
                ->countBy()
                ->sortKeys()
                ->all();

            $result[$url] = new ProbeResultsSummary(
                total: $total,
                reachable: $reachable,
                unreachable: $unreachable,
                unknown: $unknown,
                firstExecutedAt: $executed->first(),
                lastExecutedAt: $executed->last(),
                avgDurationMs: $avgDurationMs,
                statusHeaderCounts: $statusHeaderCounts,
            );
        }

        ksort($result);

        return $result;
    }
}
