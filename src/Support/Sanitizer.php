<?php

namespace Uplinkr\Support;

use Uplinkr\Objects\Config\UplinkrConfig;

/**
 * Class Sanitizer
 * @package Uplinkr\Support
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class Sanitizer
{
    public function __construct(
        private readonly UplinkrConfig $config
    )
    {
    }

    /**
     * Sanitizes the provided project name or retrieves the standard project name if null.
     *
     * @param string|null $value The input project name to be sanitized. If null, the standard project name is returned.
     * @return string The sanitized project name in lowercase.
     */
    public function project(string|null $value): string
    {
        if ($value === null) {
            return $this->config->getStandardProject();
        }

        // Replace problematic characters with hyphens (including dot)
        // - Remove control characters
        // - Reduce multiple hyphens to a single one
        // - Replace whitespace with hyphens
        // - Trim hyphens and whitespace
        $sanitized = preg_replace('/[\/\\\:*?"<>|()!$%&.]/', '-', $value);
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $sanitized);
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        $sanitized = preg_replace('/\s+/', '-', $sanitized);
        $sanitized = trim($sanitized, '- ');

        return strtolower($sanitized);
    }
}