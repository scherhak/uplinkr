<?php

namespace Uplinkr\Handler\Storage;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Uplinkr\Objects\Config\UplinkrConfig;
use Uplinkr\Support\Sanitizer;

/**
 * Class PruneHandler
 * @package Uplinkr\Handler
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class PruneHandler
{
    private readonly Sanitizer $sanitizer;

    public function __construct(
        private readonly UplinkrConfig $config,
        ?Sanitizer                     $sanitizer = null
    )
    {
        $this->sanitizer = $sanitizer ?? new Sanitizer($this->config);
    }

    /**
     * Prunes files in a specific project that are older than the given date.
     *
     * @param string $project The project identifier
     * @param string $beforeDateString Date string in Y-m-d format
     * @return int Number of deleted files
     * @throws InvalidArgumentException If the date format is invalid
     */
    public function pruneBeforeDate(string $project, string $beforeDateString): int
    {
        if (trim($project) === '') {
            return 0;
        }

        $project = $this->sanitizer->project($project);
        if ($project === '') {
            return 0;
        }

        try {
            $beforeDate = Carbon::createFromFormat('Y-m-d', $beforeDateString)?->startOfDay();
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Invalid date format: %s (ex: %s)',
                    $beforeDateString,
                    $e->getMessage())
            );
        }

        // Build path: storage_path/project/probes
        $storagePath = $this->config->getStoragePath();
        $probesDir = $this->config->getProbeResultsPath();

        $fullPath = sprintf('%s/%s/%s', $storagePath, $project, $probesDir);

        if (!Storage::disk($this->config->getStorageDisc())->exists($fullPath)) {
            return 0;
        }

        $files = Storage::disk($this->config->getStorageDisc())->files($fullPath);
        $deletedCount = 0;
        $separator = $this->config->getProbeFilenameSeparator();

        foreach ($files as $file) {
            // Get filename without extension
            $filename = pathinfo($file, PATHINFO_FILENAME);

            // Split by separator
            $parts = explode($separator, $filename);

            // Check if we have a valid structure to extract date (last part)
            if (count($parts) > 1) {
                $datePart = end($parts);

                try {
                    $fileDate = Carbon::createFromFormat('Y-m-d', $datePart)?->startOfDay();

                    if ($fileDate->lessThan($beforeDate)) {
                        Storage::disk($this->config->getStorageDisc())->delete($file);
                        $deletedCount++;
                    }
                } catch (Exception $e) {
                    // Ignore files where date parsing fails
                    continue;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Deletes the specified directory from the storage disk.
     *
     * @param string $directory The path of the directory to be deleted.
     * @return void
     */
    public function deleteDirectory(string $directory): void
    {
        Storage::disk($this->config->getStorageDisc())->deleteDirectory($directory);
    }

    /**
     * Creates a new directory in the storage disk specified in the configuration.
     *
     * @param string $directory The name or path of the directory to be created.
     * @return void
     */
    public function makeDirectory(string $directory): void
    {
        Storage::disk($this->config->getStorageDisc())->makeDirectory($directory);
    }
}
