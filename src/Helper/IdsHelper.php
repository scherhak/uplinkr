<?php

namespace Uplinkr\Helper;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Ids
 * @package App\Uplinkr
 *
 * @version 1
 * @copyright 2025-today S. Scherhak / Uplinkr
 * @author Sascha Scherhak <sascha.scherhak@uplinkr.app>
 */
class IdsHelper
{
    /**
     * @var string
     */
    public const string REQUEST_ID_PRE_CHARS = '23456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Unique user id
     *
     * @return string
     */
    public static function createUID(): string
    {
        return sprintf('U%s', self::baseId());
    }

    /**
     * @return string
     */
    public static function createCNTID(): string
    {
        return sprintf('CNT%s', self::baseId());
    }

    /**
     * @return string
     */
    public static function createRID(): string
    {
        return sprintf(
            '%s-%s',
            substr(str_shuffle(self::REQUEST_ID_PRE_CHARS), 0, 6),
            self::baseId()
        );
    }

    /**
     * @return string
     */
    public static function createOTPID(): string
    {
        return sprintf('OTP%s', self::baseId());
    }

    public static function createPRBID(): string
    {
        return sprintf('PRB%s', self::baseId());
    }

    public static function createPRJID(): string
    {
        return sprintf('PRB%s', self::baseId());
    }

    public static function createURIID(): string
    {
        return sprintf('URI%s', self::baseId());
    }

    public static function createINTVID(): string
    {
        return sprintf('INTV%s', self::baseId());
    }

    public static function createALRTID(): string
    {
        return sprintf('ALRT%s', self::baseId());
    }

    /**
     * Request Body
     *
     * @return string
     */
    public static function createRQBD(): string
    {
        return sprintf('RQBD%s', self::baseId());
    }

    /**
     * Creates an id string from Ramysey's uuid without slashes
     * @see https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/
     *
     * @return UuidInterface
     */
    private static function baseId(): string
    {
        return Uuid::uuid6();
    }
}
