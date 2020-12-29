<?php

namespace App\Service;

use App\Util\Resolution;
use App\Util\Types;
use DateTime;

/**
 * Class Path
 * @package App\Service
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Path
{
    /**
     * @var string
     */
    private $projectDir;


    /**
     * Constructor.
     *
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Builds the wind file path.
     *
     * @param DateTime $date
     * @param string   $resolution
     * @param string   $type
     *
     * @return string
     */
    public function build(DateTime $date, string $resolution, string $type): string
    {
        Resolution::validate($resolution);
        Types::validate($type);

        return sprintf(
            '%s/var/wind/%s/%s/%s-%s.wind',
            $this->projectDir,
            $date->format('Ymd'),
            $date->format('H'),
            $resolution,
            $type
        );
    }
}
