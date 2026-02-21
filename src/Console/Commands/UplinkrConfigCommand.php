<?php

namespace Uplinkr\Console\Commands;

use Illuminate\Console\Command;
use JsonException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Uplinkr\Support\CliIcon;

/**
 * Class UplinkrConfigCommand
 * @package Uplinkr\Console\Commands
 *
 * This class is responsible for handling the execution of the `uplinkr:config` command.
 * It displays the current Uplinkr configuration in a structured format.
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class UplinkrConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uplinkr:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current Uplinkr configuration';

    /**
     * Execute the console command.
     *
     * Displays the current Uplinkr configuration in a structured, readable format
     * similar to Laravel's native config:show command.
     *
     * @return int Returns the status code indicating the success of the operation.
     * @throws JsonException
     */
    public function handle(): int
    {
        $config = config('uplinkr');

        if (empty($config)) {
            $this->error(CliIcon::ERROR->label(text: 'Uplinkr configuration not found. Run uplinkr:install first.'));
            return self::FAILURE;
        }

        $this->info(CliIcon::INFO->label(text: 'Current Uplinkr Configuration'));
        $this->newLine();

        $this->displayConfig($config);

        return self::SUCCESS;
    }

    /**
     * Display configuration recursively with proper formatting.
     *
     * @param array $config The configuration array to display
     * @param string $prefix The prefix for nested keys
     * @param int $depth Current depth level for indentation
     * @return void
     * @throws JsonException
     */
    private function displayConfig(array $config, string $prefix = '', int $depth = 0): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $indent = str_repeat('  ', $depth);
            $escapedKey = OutputFormatter::escape((string)$fullKey);

            if (is_array($value)) {
                if ($value === []) {
                    $this->line("{$indent}<fg=yellow>{$escapedKey}</>: <fg=cyan>[]</>");
                    continue;
                }

                // Display section header
                $this->line("{$indent}<fg=green>{$escapedKey}</>");
                $this->displayConfig($value, $fullKey, $depth + 1);
            } else {
                // Display key-value pair
                $displayValue = $this->formatValue($value);
                $escapedValue = OutputFormatter::escape($displayValue);
                $this->line("{$indent}<fg=yellow>{$escapedKey}</>: <fg=cyan>{$escapedValue}</>");
            }
        }

        // Add spacing after top-level sections
        if ($depth === 0) {
            $this->newLine();
        }
    }

    /**
     * Format configuration values for display.
     *
     * @param mixed $value The value to format
     * @return string The formatted value as string
     * @throws JsonException
     */
    private function formatValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_string($value) => $value === '' ? '""' : $value,
            is_numeric($value) => (string)$value,
            default => json_encode($value, JSON_THROW_ON_ERROR)
        };
    }
}
