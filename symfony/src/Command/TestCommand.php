<?php

namespace App\Command;

use App\Service\Converter;
use App\Service\Downloader;
use App\Service\Packer;
use App\Util\Resolution;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TestCommand
 * @package App\Command
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class TestCommand extends Command
{
    protected static $defaultName = 'app:test';

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
     * @var string
     */
    private $projectDir;


    /**
     * Constructor.
     *
     * @param Downloader $downloader
     * @param Converter  $converter
     * @param Packer     $packer
     * @param string     $projectDir
     */
    public function __construct(Downloader $downloader, Converter $converter, Packer $packer, string $projectDir)
    {
        parent::__construct();

        $this->downloader = $downloader;
        $this->converter = $converter;
        $this->packer = $packer;
        $this->projectDir = $projectDir;
        $this->filesystem = new Filesystem();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->test($output);

        //$this->generateBin($output);
        //$this->generateCsv($output);

        return 0;
    }

    private function test(OutputInterface $output): void
    {
        $data = [
            0.090741,
            -0.619259,
            -1.67926,
            -1.30926,
            -0.949259,
            -1.04926,
            -2.76926,
            0.480741,
            -1.56926,
            -3.36926,
            -1.54926,
            -1.78926,
            -4.69926,
            -1.12926,
            0.540741,
            1.63074,
            3.01074,
            0.670741,
            0.580741,
            -1.50926,
            -1.86926,
            -4.87926,
            -6.35926,
            -7.05926,
            -7.53926,
            -8.07926,
            -9.31926,
            -10.3293,
            -12.0993,
            -13.0493,
            -12.5293,
            -11.6093,
            -11.1193,
        ];

        $factor = 100;

        $data = array_map(function ($v) use ($factor) {
            return (int)($v * $factor);
        }, $data);

        $data = pack('s*', ...$data);

        $data = unpack('s*', $data);

        foreach ($data as $datum) {
            $output->writeln($datum / $factor);
        }

        return;

        $path = sprintf('%s/var/wind/1p00/ds/000.wind', $this->projectDir);

        $h = fopen($path, 'rb');

        for ($i = 0; $i < 10; $i++) {
            if (false !== $data = fread($h, 2)) {
                $data = unpack('s', $data);
                $output->writeln($data);
            }
        }

        fclose($h);
    }

    private function generateBin(OutputInterface $output): void
    {
        // DOWNLOAD
        $path = $this->downloader->download(new DateTime(), Resolution::RESOLUTION_1P00);

        // CONVERT
        // Extract wind direction from grib file
        $dGrib = $this->converter->extractWindDirection($path);
        // Convert wind direction into binary
        $wdCsv = $this->converter->toBin($dGrib, 'wd_');
        // Delete wind direction grib file
        $this->filesystem->remove($dGrib);
        // Move Bin
        $this->moveFile($wdCsv, sprintf('%s/var/tmp/wd.bin', $this->projectDir));

        // Extract wind speed from grib file
        $sGrib = $this->converter->extractWindSpeed($path);
        // Convert wind speed into binary
        $sCsv = $this->converter->toBin($sGrib, 'ws_');
        // Delete wind speed grib file
        $this->filesystem->remove($sGrib);
        // Move Bin
        $this->moveFile($sCsv, sprintf('%s/var/tmp/ws.bin', $this->projectDir));

        // CONVERT
        // Extract wind direction from grib file
        $uBin = $this->converter->extractWindU($path);
        // Move Bin
        $this->moveFile($uBin, sprintf('%s/var/tmp/wu.bin', $this->projectDir));

        // Extract wind speed from grib file
        $vBin = $this->converter->extractWindV($path);
        // Move Bin
        $this->moveFile($vBin, sprintf('%s/var/tmp/wv.bin', $this->projectDir));

        // Delete grib source file
        $this->filesystem->remove($path);
    }

    private function generateCsv(OutputInterface $output): void
    {
        // DOWNLOAD
        $path = $this->downloader->download(new DateTime(), Resolution::RESOLUTION_1P00);

        // CONVERT
        // Extract wind direction from grib file
        $dGrib = $this->converter->extractWindDirection($path);
        // Convert wind direction into binary
        $wdCsv = $this->converter->toCsv($dGrib, 'wd_');
        // Delete wind direction grib file
        $this->filesystem->remove($dGrib);
        // Move CSV
        $this->moveFile($wdCsv, sprintf('%s/var/tmp/wd.csv', $this->projectDir));

        // Extract wind speed from grib file
        $sGrib = $this->converter->extractWindSpeed($path);
        // Convert wind speed into binary
        $sCsv = $this->converter->toCsv($sGrib, 'ws_');
        // Delete wind speed grib file
        $this->filesystem->remove($sGrib);
        // Move CSV
        $this->moveFile($sCsv, sprintf('%s/var/tmp/ws.csv', $this->projectDir));

        // CONVERT
        // Extract wind direction from grib file
        $uBin = $this->converter->extractWindU($path, false);
        // Move Bin
        $this->moveFile($uBin, sprintf('%s/var/tmp/wu.txt', $this->projectDir));

        // Extract wind speed from grib file
        $vBin = $this->converter->extractWindV($path, false);
        // Move Bin
        $this->moveFile($vBin, sprintf('%s/var/tmp/wv.txt', $this->projectDir));

        // Delete grib source file
        $this->filesystem->remove($path);
    }

    /**
     * Moves the file.
     *
     * @param string $source
     * @param string $destination
     */
    private function moveFile(string $source, string $destination): void
    {
        $this->createDirectory(dirname($destination));

        // Delete if exist
        if ($this->filesystem->exists($destination)) {
            $this->filesystem->remove($destination);
        }

        $this->filesystem->rename($source, $destination);
    }

    /**
     * Creates the directory if it not exists.
     *
     * @param string $path
     */
    private function createDirectory(string $path): void
    {
        if ($this->filesystem->exists($path)) {
            return;
        }

        $this->filesystem->mkdir($path);
    }
}
