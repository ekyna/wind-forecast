<?php

namespace App\Service;

use App\Util\Resolution;
use DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Class Generator
 * @package App\Service
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Generator
{
    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var Packer
     */
    private $packer;

    /**
     * @var Filesystem
     */
    private $filesystem;


    /**
     * Constructor.
     *
     * @param Downloader $downloader
     * @param Converter  $converter
     * @param Packer     $packer
     */
    public function __construct(Downloader $downloader, Converter $converter, Packer $packer)
    {
        $this->downloader = $downloader;
        $this->converter = $converter;
        $this->packer = $packer;
        $this->filesystem = new Filesystem();
    }

    /**
     * Generate wind direction/speed data binary file.
     *
     * @param DateTime $date
     * @param string   $resolution
     *
     * @return string
     *
     * @throws Throwable
     */
    public function generateWindDS(DateTime $date, string $resolution): string
    {
        Resolution::validate($resolution);

        // DOWNLOAD
        $path = $this->downloader->download($date, $resolution);

        // CONVERT
        // Extract wind direction from grib file
        $dGrib = $this->converter->extractWindDirection($path);
        // Convert wind direction into binary
        $dBin = $this->converter->toBin($dGrib, 'wind_direction_');
        // Delete wind direction grib file
        $this->filesystem->remove($dGrib);

        // Extract wind speed from grib file
        $sGrib = $this->converter->extractWindSpeed($path);
        // Convert wind speed into binary
        $sBin = $this->converter->toBin($sGrib, 'wind_speed_');
        // Delete wind speed grib file
        $this->filesystem->remove($sGrib);

        // Delete grib source file
        $this->filesystem->remove($path);

        // Pack direction and speed data
        try {
            $pack = $this->packer->packDirSpeed($dBin, $sBin);
        } catch (Throwable $e) {
            // Delete binary source files
            $this->filesystem->remove([$dBin, $sBin]);

            throw $e;
        }

        // Delete binary source files
        $this->filesystem->remove([$dBin, $sBin]);

        return $pack;
    }

    /**
     * Generate wind UV data binary file.
     *
     * @param DateTime $date
     * @param string   $resolution
     *
     * @return string
     *
     * @throws Throwable
     */
    public function generateWindUV(DateTime $date, string $resolution): string
    {
        Resolution::validate($resolution);

        // DOWNLOAD
        $path = $this->downloader->download($date, $resolution);

        // CONVERT
        // Extract wind U from grib file
        $uBin = $this->converter->extractWindU($path);
        // Extract wind V from grib file
        $vBin = $this->converter->extractWindV($path);
        // Delete grib source file
        $this->filesystem->remove($path);

        // Pack direction and speed data
        try {
            $pack = $this->packer->packUV($uBin, $vBin);
        } catch (Throwable $e) {
            // Delete binary source files
            $this->filesystem->remove([$uBin, $vBin]);

            throw $e;
        }

        // Delete binary source files
        $this->filesystem->remove([$uBin, $vBin]);

        return $pack;
    }
}
