<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class GribFile
 * @package App\Service
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Converter
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var Filesystem
     */
    private $filesystem;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->setDirectory();
    }

    /**
     * Sets the temporary files directory.
     *
     * @param string|null $path
     */
    public function setDirectory(string $path = null): void
    {
        if (is_null($path)) {
            $this->directory = sys_get_temp_dir();

            return;
        }

        $this->directory = rtrim($path, '/');

        if ($this->filesystem->exists($this->directory)) {
            return;
        }

        $this->filesystem->mkdir($this->directory);
    }

    /**
     * Extracts wind U data from the given grib file.
     *
     * @param string $source The source grib file path
     * @param bool   $bin    Whether to output as binary or text (default bin)
     *
     * @return string
     */
    public function extractWindU(string $source, bool $bin = true): string
    {
        $target = tempnam($this->directory, 'wu_');

        $this->run([
            $source,
            '-match',
            ':UGRD:',
            '-match',
            ':10 m above ground:',
            ($bin ? '-bin' : '-text'),
            $target,
        ]);

        return $target;
    }

    /**
     * Extracts wind V data from the given grib file.
     *
     * @param string $source The source grib file path
     * @param bool   $bin    Whether to output as binary or text (default bin)
     *
     * @return string
     */
    public function extractWindV(string $source, bool $bin = true): string
    {
        $target = tempnam($this->directory, 'wv_');

        $this->run([
            $source,
            '-match',
            ':VGRD:',
            '-match',
            ':10 m above ground:',
            ($bin ? '-bin' : '-text'),
            $target,
        ]);

        return $target;
    }

    /**
     * Extracts wind speed data from the given grib file.
     *
     * @param string $source The source grib file path
     *
     * @return string
     */
    public function extractWindSpeed(string $source): string
    {
        $target = tempnam($this->directory, 'ws_');

        $this->run([
            $source,
            '-match',
            '(:UGRD:|:VGRD:)',
            '-match',
            ':10 m above ground:',
            '-wind_speed',
            $target,
        ]);

        return $target;
    }

    /**
     * Extracts wind direction data from the given grib file.
     *
     * @param string $source The source grib file path
     *
     * @return string
     */
    public function extractWindDirection(string $source): string
    {
        $target = tempnam($this->directory, 'wd_');

        $this->run([
            $source,
            '-match',
            '(:UGRD:|:VGRD:)',
            '-match',
            ':10 m above ground:',
            '-wind_dir',
            $target,
        ]);

        return $target;
    }

    /**
     * Converts this grib file into binary data file.
     *
     * @param string      $source The source grib file path
     * @param string|null $name   The temporary file prefix
     *
     * @return string The CSV absolute file path.
     */
    public function toBin(string $source, string $name = null): string
    {
        $target = tempnam($this->directory, $name ?: 'wb_');

        $this->run([
            $source,
            '-bin',
            $target,
        ]);

        return $target;
    }

    /**
     * Converts this grib file into CSV.
     *
     * @param string $source The source grib file path
     *
     * @return string The CSV absolute file path.
     */
    public function toCsv(string $source, string $name = null): string
    {
        $target = tempnam($this->directory, $name ?: 'wb_');

        $this->run([
            $source,
            '-spread',
            $target,
        ]);

        return $target;
    }

    /**
     * Runs wgrib2 command.
     *
     * @param array $args
     */
    private function run(array $args): void
    {
        $command = ['/usr/local/bin/wgrib2'];

        array_push($command, ...$args);

        $process = new Process($command);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
