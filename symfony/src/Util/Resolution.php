<?php

namespace App\Util;

use InvalidArgumentException;

/**
 * Class Resolution
 * @package App\Util
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
final class Resolution
{
    public const RESOLUTION_0P25 = '0p25';
    public const RESOLUTION_1P00 = '1p00';
    public const RESOLUTION_0P50 = '0p50';


    /**
     * Validates the resolution.
     *
     * @param string $resolution
     */
    public static function validateResolution(string $resolution): void
    {
        if (in_array($resolution, self::getResolutions(), true)) {
            return;
        }

        throw new InvalidArgumentException("Unexpected resolution '$resolution'.");
    }

    /**
     * Returns the resolutions.
     *
     * @return string[]
     */
    public static function getResolutions(): array
    {
        return [
            self::RESOLUTION_1P00,
            self::RESOLUTION_0P50,
            self::RESOLUTION_0P25,
        ];
    }

    /** Disabled constructor */
    private function __construct() {}
}
