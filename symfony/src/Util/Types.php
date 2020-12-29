<?php

namespace App\Util;

use InvalidArgumentException;

/**
 * Class Types
 * @package App\Util
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
final class Types
{
    public const UV = 'uv';
    public const DS = 'ds';


    /**
     * Validates the given type.
     *
     * @param string $type
     */
    public static function validate(string $type): void
    {
        if (in_array($type, self::getTypes(), true)) {
            return;
        }

        throw new InvalidArgumentException("Unexpected type '$type'.");
    }

    /**
     * Returns all the types.
     *
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            self::UV,
            self::DS,
        ];
    }

    /** Disabled constructor */
    private function __construct()
    {

    }
}
