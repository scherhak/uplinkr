<?php

namespace Uplinkr\Handler\Project;

use DateTimeImmutable;
use Exception;
use Storage;
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

//    public function readProbeResultFile(string $path)
//    {
//        return Storage::disk($this->config->getStorageDisc())->get($path);
//    }


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
        return array_filter($files, function (string $file) use ($fromDate, $toDate) {

            $date = $this->extractDateFromFilename($file);

            // Skip files that do not contain a parsable date
            if (null === $date) {
                return false;
            }

            $fileDate = new DateTimeImmutable($date);

            if ((null !== $fromDate)) {
                if($fileDate < new DateTimeImmutable($fromDate)) {
                    return false;
                }
            }

            if ((null !== $toDate)) {
                if($fileDate > new DateTimeImmutable($toDate)) {
                    return false;
                }
            }

            return true;
        });
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
