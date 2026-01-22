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
    /**
     * Constructor.
     *
     * @param UplinkrConfig $config
     */
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

    /**
     * Sanitizes the provided URL by normalizing its scheme and extracting the host, replacing non-alphanumeric characters with underscores.
     *
     * @param string $url The input URL to be sanitized. If the URL does not include a scheme, "http://" is prepended by default.
     * @return string The sanitized host portion of the URL in lowercase, with non-alphanumeric characters replaced by underscores.
     */
    public function url(string $url): string
    {
        if (!preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $url)) {
            $url = 'http://' . ltrim($url, '/');
        }

        // Extract only the host (domain), e.g., "scherhak.com"
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $host = preg_replace('/^www\./i', '', $host);

        // Everything that is not a letter/number should be changed to "_"
        $sanitized = preg_replace('/[^A-Za-z0-9]+/', '-', $host);

        // Shrink multiple "_" lines and trim edges
        $sanitized = trim(preg_replace('/-+/', '-', $sanitized), '-');

        return strtolower($sanitized);
    }
}