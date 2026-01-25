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
    case INFO = 'ℹ';
    case OK = '✔';
    case WARN = '⚠';
    case ERROR = '✖';
    case DEBUG = '⌁';

    // Flow / actions
    case RUN = '▶';
    case NEXT = '➜';
    case ARROW = '→';
    case BULLET = '•';
    case DOT = '·';

    // Monitoring / status
    case UP = '▲';
    case DOWN = '▼';
    case CLOCK = '⏱';
    case RETRY = '↻';
    case SKIP = '↷';

    // Files / persistence
    case SAVE = '💾';
    case WRITE = '✎';
    case READ = '⌄';

    /**
     * Returns a formatted label by combining the instance value and the provided text.
     *
     * @param string $text The text to be appended to the instance value.
     * @return string The formatted label combining the instance value and the text.
     */
    public function label(string $text): string
    {
        return sprintf('%s %s', $this->value, $text);
    }

    /**
     * Returns the ASCII string representation for the current instance of the enum.
     *
     * @return string The ASCII representation associated with the enum value.
     */
    public function ascii(): string
    {
        return match ($this) {
            self::OK => '[OK]',
            self::WARN => '[WARN]',
            self::ERROR => '[ERR]',
            self::INFO => '[i]',
            self::DEBUG => '[dbg]',

            self::RUN => '>',
            self::NEXT,
            self::ARROW => '->',

            self::BULLET => '-',
            self::DOT => '.',

            self::UP => 'UP',
            self::DOWN => 'DOWN',
            self::CLOCK => 'TIME',
            self::RETRY => 'RETRY',
            self::SKIP => 'SKIP',

            self::SAVE => 'SAVE',
            self::WRITE => 'WRITE',
            self::READ => 'READ',
        };
    }
}
