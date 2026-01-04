<?php

namespace Uplinkr\Support;

/**
 * Class Time
 * @package Uplinkr\Support
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class Time
{
    /**
     * Gets the current date and time as a formatted string.
     *
     * @return string The current date and time in the datetime string format.
     */
    public static function now(): string
    {
        return now()->toDateTimeString();
    }
}