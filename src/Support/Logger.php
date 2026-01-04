<?php

namespace Uplinkr\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Class Logger
 * @package Uplinkr\Support
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
class Logger
{
    /**
     * Retrieves a logger instance for the specified log channel.
     *
     * @return LoggerInterface The logger instance for the configured log channel.
     */
    public static function log(): LoggerInterface
    {
        $channel = config('uplinkr.log_channel', 'uplinkr');

        return Log::channel($channel);
    }
}