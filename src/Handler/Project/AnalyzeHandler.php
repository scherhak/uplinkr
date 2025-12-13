<?php

namespace Uplinkr\Handler\Project;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class AnalyzeHandler
 * @package Uplinkr\Handler\Analysis
 *
 *
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
     * Reads a probe result file and returns its content line by line as an array.
     * Each line in the file is expected to be a JSON string.
     *
     * @param string $path
     * @return array
     */
    public function readProbeResultFile(string $path): array
    {
        $disk = Storage::disk($this->config->getStorageDisc());
        $content = $disk->get($path);

        if ($content === null || $content === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];

        return collect($lines)
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => $line !== '')
            ->values()
            ->all();
    }

    public function decodeProbeResultLines(array $lines): array
    {
        return collect($lines)
            ->map(static function (string $line): ?array {
                $line = trim($line);

                if ($line === '') {
                    return null;
                }

                $decoded = json_decode($line, true);

                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    // Optional: Log::warning('Invalid probe result JSON line', ['line' => $line]);
                    return null;
                }

                return $decoded;
            })
            ->filter(static fn ($decoded) => is_array($decoded))
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
    private function extractDateFromFilename(string $path): ?string
    {
        $filename = basename($path);

        if (preg_match('/@(\d{4}-\d{2}-\d{2})\./', $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
