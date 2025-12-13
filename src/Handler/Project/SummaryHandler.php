<?php

namespace Uplinkr\Handler\Project;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Uplinkr\Objects\Summary\ProbeResultsSummary;
use Uplinkr\Support\Sanitizer;

/**
 * Class SummaryHandler
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class SummaryHandler
{
    public function __construct(
        private readonly Sanitizer $sanitizer
    )
    {
    }

    /**
     * Summarizes the probe results by grouping them based on their URL and calculating various statistics.
     *
     * @param array $decodedLines An array of decoded lines representing probe data, where each line is an array of attributes.
     *                            Each line should contain relevant fields such as 'settings.url', 'probe_status',
     *                            'probe_message.duration_ms', 'executed', and 'status_header'.
     *
     * @return array Returns an associative array where each key is a URL, and the value is an instance of ProbeResultsSummary
     *               containing details like total count, reachable count, unreachable count, error count, unknown count,
     *               first/last execution timestamps, average duration in milliseconds, and status header counts.
     */
    public function summarizeProbeResults(array $decodedLines): array
    {
        $groups = $this->groupByUrl($decodedLines);

        $result = [];

        foreach ($groups as $url => $items) {
            $total = $items->count();

            $reachable = $items->where('probe_status', 'reachable')->count();
            $unreachable = $items->where('probe_status', 'unreachable')->count();
            $error = $items->where('probe_status', 'error')->count();
            $unknown = $items->where('probe_status', 'unknown')->count();

            $unknown = max($unknown, $total - $reachable - $unreachable - $error - $items->where('probe_status', 'error')->count());
            $executed = $this->setExecuted(items: $items);
            $durations = $this->setDurations(items: $items);
            $avgDurationMs = $durations->isEmpty() ? null : round($durations->avg(), 2);
            $statusHeaderCounts = $this->setStatusHeaderCounts(items: $items);

            $result[$this->sanitizer->url($url)] = new ProbeResultsSummary(
                url: $url,
                total: $total,
                reachable: $reachable,
                unreachable: $unreachable,
                error: $error,
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

    /**
     * Groups an array of decoded lines by their URL.
     *
     * @param array $decodedLines An array of decoded lines representing probe data, where each line is an array of attributes.
     *                            Each line is expected to include a 'settings.url' field, which indicates the URL used for grouping.
     *
     * @return Collection Returns a collection grouped by the trimmed 'settings.url' value.
     *                                         Only valid lines containing a non-empty string URL will be included in the result.
     */
    private function groupByUrl(array $decodedLines): Collection
    {
        return collect($decodedLines)
            ->filter(static fn($line) => is_array($line))
            ->filter(static fn($line) => is_string(
                    Arr::get($line, 'settings.url')) && trim(Arr::get($line, 'settings.url', '')) !== '')
            ->groupBy(static fn($line) => trim(Arr::get($line, 'settings.url')));
    }

    /**
     * Extracts and returns a collection of executed durations from the given collection of probe items.
     * Filters out non-numeric or invalid duration values, converts the remaining values to floats, and re-indexes the collection.
     *
     * @param Collection $items A collection of probe items, where each item is an array of attributes.
     *                           Each item should contain the 'probe_message.duration_ms' field that represents the execution duration.
     *
     * @return Collection Returns a collection of numeric durations extracted from the input collection, re-indexed and filtered for validity.
     */
    private function setExecuted(Collection $items): Collection
    {
        return $items
            ->map(static fn($line) => Arr::get($line, 'probe_message.duration_ms'))
            ->filter(static fn($v) => is_numeric($v))
            ->map(static fn($v) => (float)$v)
            ->values();
    }

    /**
     * Extracts and processes the duration values from the given collection of items.
     *
     * @param Collection $items A collection of items, where each item is expected to contain a
     *                           'probe_message.duration_ms' field representing the duration in milliseconds.
     *
     * @return Collection Returns a collection of numeric duration values, filtered and converted to floats.
     */
    private function setDurations(Collection $items): Collection
    {
        return $items
            ->map(static fn($line) => Arr::get($line, 'probe_message.duration_ms'))
            ->filter(static fn($v) => is_numeric($v))
            ->map(static fn($v) => (float)$v)
            ->values();
    }

    /**
     * Processes the status headers from a collection of items and calculates their frequency counts.
     *
     * @param Collection $items A collection of items where each item contains a 'status_header' field.
     *                          The 'status_header' field should represent an integer or a string that can be cast to an integer.
     *
     * @return array Returns an associative array where the keys are sorted status header values (as integers),
     *               and the values are their corresponding counts within the collection.
     */
    private function setStatusHeaderCounts(Collection $items): array
    {
        return $items
            ->pluck('status_header')
            ->filter(static fn($v) => is_int($v) || ctype_digit((string)$v))
            ->map(static fn($v) => (int)$v)
            ->countBy()
            ->sortKeys()
            ->all();
    }
}
