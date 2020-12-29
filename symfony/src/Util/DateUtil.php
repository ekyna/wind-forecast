<?php

namespace App\Util;

use DateTime;
use DateTimeZone;
use RuntimeException;

/**
 * Class DateUtil
 * @package App\Util
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
final class DateUtil
{
    private const TIMEZONE = 'UTC';

    /**
     * Creates a new UTC date time object, initializes with the current timestamp.
     *
     * @return DateTime
     */
    public static function createUTC(): DateTime
    {
        $date = new DateTime();

        $date->setTimezone(new DateTimeZone(self::TIMEZONE));
        $date->setTime($date->format('G'), 0);

        return $date;
    }

    /**
     * Checks whether the date has the proper timezone.
     *
     * @param DateTime $date
     *
     * @return bool
     */
    public static function checkTimezone(DateTime $date): bool
    {
        return self::TIMEZONE === $date->getTimezone()->getName();
    }

    /**
     * Checks if the date is rounded by the given hours step.
     *
     * @param DateTime $date
     * @param int      $step
     *
     * @return bool
     */
    public static function checkStep(DateTime $date, int $step): bool
    {
        return self::checkTimezone($date) && (0 === $date->format('G') % $step);
    }

    /**
     * Rounds the date by the given hours step.
     *
     * @param DateTime|null $date
     * @param int           $step
     *
     * @return DateTime
     */
    public static function roundStep(DateTime $date = null, int $step = 3): DateTime
    {
        $date = $date ? clone $date : self::createUTC();

        if (!self::checkTimezone($date)) {
            throw new RuntimeException("Expected UTC date time object.");
        }

        if (!self::checkStep($date, $step)) {
            $date->setTime(intval($date->format('G') / $step) * $step, 0);
        }

        return $date;
    }

    /** Disabled constructor */
    private function __construct()
    {
    }
}
