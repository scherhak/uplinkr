<?php

namespace Uplinkr\Handler\Project;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Throwable;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class AnalyzeHandler
 * @package Uplinkr\Handler\Analysis
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class AnalyzeHandler
{
    public function __construct(
        private readonly UplinkrConfig $config,
    )
    {
    }

    /**
     * Handles the retrieval of files for a specific project, optionally filtering by date range.
     *
     * @param string $project The name of the project whose files are to be retrieved.
     * @param string|null $fromDate The start of the date range filter (inclusive), formatted as YYYY-MM-DD, or null to skip the lower bound.
     * @param string|null $toDate The end of the date range filter (inclusive), formatted as YYYY-MM-DD, or null to skip the upper bound.
     *
     * @return array An array of file paths from the project's probe results directory, filtered by the specified date range if provided.
     * @throws Exception
     */
    public function probeResultsList(string $project, ?string $fromDate = null, ?string $toDate = null): array
    {
        $disk = Storage::disk($this->config->getStorageDisc());

        // Probe folder for the project
        $probeDir = sprintf(
            '%s/%s/%s',
            $this->config->getStoragePath(),
            $project,
            $this->config->getProbeResultsPath()
        );

        if (!$disk->exists($probeDir)) {
            return [];
        }

        // Get all files from the folder
        $files = $disk->files($probeDir);

        // Filter by date range if needed
        if (null !== $fromDate || null !== $toDate) {
            $files = $this->filterFilesByDateRange($files, $fromDate, $toDate);
        }

        return array_values($files);
    }

    /**
     * Reads a probe result file and returns its content as a string.
     *
     * @param string $path
     * @return string
     */
    public function readProbeResultFile(string $path): string
    {
        $disk = Storage::disk($this->config->getStorageDisc());
        return $disk->get($path) ?: '';
    }

    /**
     * Decodes a JSON-encoded probe result file content.
     *
     * @param string $content The JSON-encoded content of a probe result file.
     *
     * @return array An array of probe results.
     */
    public function decodeProbeResults(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return [];
            }

            return $decoded;
        } catch (Throwable) {
            // If there are still old files in JSONL format, we will attempt to read them line by line.
            return $this->decodeProbeResultLines(explode("\n", $content));
        }
    }

    /**
     * Decodes and filters an array of JSON-encoded probe result lines (legacy JSONL support).
     *
     * @param array $lines An array of strings, each representing a JSON-encoded probe result line.
     *
     * @return array An array of successfully decoded associative arrays, filtered to remove invalid and empty lines.
     */
    public function decodeProbeResultLines(array $lines): array
    {
        return collect($lines)
            ->map(static function (string $line): ?array {
                $line = trim($line);

                if ($line === '') {
                    return null;
                }

                $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                    // TODO Think about logging invalid lines
                    // Log::warning('Invalid probe result JSON line', ['line' => $line]);
                    return null;
                }

                return $decoded;
            })
            ->filter(static fn($decoded) => is_array($decoded))
            ->values()
            ->all();
    }

    /**
     * Filters an array of file paths based on a specified date range.
     *
     * @param array $files An array of file paths to filter.
     * @param string|null $fromDate The start date of the range in "YYYY-MM-DD" format. Files with dates earlier than this will be excluded. Null means no start date restriction.
     * @param string|null $toDate The end date of the range in "YYYY-MM-DD" format. Files with dates later than this will be excluded. Null means no end date restriction.
     *
     * @return array An array of file paths that fall within the specified date range.
     * @throws Exception
     */
    private function filterFilesByDateRange(array $files, ?string $fromDate, ?string $toDate): array
    {
        $from = $fromDate ? CarbonImmutable::parse($fromDate)->startOfDay() : null;
        $to = $toDate ? CarbonImmutable::parse($toDate)->endOfDay() : null;

        return collect($files)
            ->filter(function (string $file) use ($from, $to) {
                $dateStr = $this->extractDateFromFilename($file);
                if ($dateStr === null) {
                    return false;
                }

                try {
                    $fileDate = CarbonImmutable::parse($dateStr);
                } catch (Throwable) {
                    return false;
                }

                if ($from && $fileDate->lt($from)) {
                    return false;
                }

                if ($to && $fileDate->gt($to)) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * Extract "@YYYY-MM-DD" from a filename.
     */
    public function extractDateFromFilename(string $path): ?string
    {
        $filename = basename($path);

        if (preg_match('/@(\d{4}-\d{2}-\d{2})\./', $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Saves the analyzed results to a file named 'analyzed.json' in the project folder.
     *
     * @param string $project The name of the project.
     * @param array $results The analyzed results to be saved.
     *
     * @return void
     * @throws JsonException
     */
    public function saveAnalyzedResults(string $project, array $results): void
    {
        $disk = Storage::disk($this->config->getStorageDisc());

        $path = sprintf(
            '%s/%s/analyzed.json',
            $this->config->getStoragePath(),
            $project
        );

        $existingData = [];
        if ($disk->exists($path)) {
            $content = $disk->get($path);
            if ($content) {
                $existingData = json_decode($content, true, 512, JSON_THROW_ON_ERROR) ?: [];
            }
        }

        // Deep merge of results into existing data
        foreach ($results as $urlSlug => $dates) {
            if (!isset($existingData[$urlSlug])) {
                $existingData[$urlSlug] = [];
            }
            foreach ($dates as $date => $stats) {
                $existingData[$urlSlug][$date] = $stats;
            }
            ksort($existingData[$urlSlug]);
        }
        ksort($existingData);

        $disk->put($path, json_encode($existingData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
