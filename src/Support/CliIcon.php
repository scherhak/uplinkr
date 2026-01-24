<?php

namespace Uplinkr\Support;

/**
 * Class CliIcon
 * @package Uplinkr\Support
 *
 * @author Sascha Scherhak <sascha@uplinkr.dev>
 */
enum CliIcon: string
{
    // General
    case INFO   = 'ℹ';
    case OK     = '✔';
    case WARN   = '⚠';
    case ERROR  = '✖';
    case DEBUG  = '⌁';

    // Flow / actions
    case RUN    = '▶';
    case NEXT   = '➜';
    case ARROW  = '→';
    case BULLET = '•';
    case DOT    = '·';

    // Monitoring / status
    case UP     = '▲';
    case DOWN   = '▼';
    case CLOCK  = '⏱';
    case RETRY  = '↻';
    case SKIP   = '↷';

    // Files / persistence
    case SAVE   = '💾';
    case WRITE  = '✎';
    case READ   = '⌄';

    /**
     * Human readable label
     */
    public function label(string $text): string
    {
        return sprintf('%s %s', $this->value, $text);
    }

    /**
     * ASCII-safe fallback (optional)
     */
    public function ascii(): string
    {
        return match ($this) {
            self::OK     => '[OK]',
            self::WARN   => '[WARN]',
            self::ERROR  => '[ERR]',
            self::INFO   => '[i]',
            self::DEBUG  => '[dbg]',

            self::RUN    => '>',
            self::NEXT,
            self::ARROW  => '->',

            self::BULLET => '-',
            self::DOT    => '.',

            self::UP     => 'UP',
            self::DOWN   => 'DOWN',
            self::CLOCK  => 'TIME',
            self::RETRY  => 'RETRY',
            self::SKIP   => 'SKIP',

            self::SAVE   => 'SAVE',
            self::WRITE  => 'WRITE',
            self::READ   => 'READ',
        };
    }
}
