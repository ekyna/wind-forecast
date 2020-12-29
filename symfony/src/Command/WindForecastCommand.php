<?php

namespace App\Command;

use App\Service\Generator;
use App\Service\Path;
use App\Util\DateUtil;
use App\Util\Resolution;
use App\Util\Types;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Class WindForecastCommand
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class WindForecastCommand extends Command
{
    protected static $defaultName = 'app:wind-forecast';

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var Path
     */
    private $path;

    /**
     * @var Filesystem
     */
    private $filesystem;


    /**
     * Constructor.
     *
     * @param Generator $generator
     * @param Path      $path
     */
    public function __construct(Generator $generator, Path $path)
    {
        parent::__construct();

        $this->generator = $generator;
        $this->path = $path;
        $this->filesystem = new Filesystem();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'resolution',
                InputArgument::OPTIONAL,
                'The wind resolution to download and pack.',
                Resolution::RESOLUTION_1P00
            )
            ->addOption('steps', 's', InputOption::VALUE_REQUIRED, 'Number of steps (3 hours)', 8);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resolution = $input->getArgument('resolution');
        $steps = (int)$input->getOption('steps');

        Resolution::validate($resolution);

        $date = DateUtil::roundStep();

        // 24h - 3h step
        for ($i = 0; $i <= $steps; $i++) {
            // DS
            try {
                $this->packWindDirSpeed($date, $resolution);
            } catch (Throwable $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");

                return 1;
            }

            // UV
            try {
                $this->packWindUV($date, $resolution);
            } catch (Throwable $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");

                return 1;
            }

            $date->modify('+3 hours');
        }

        return 0;
    }

    /**
     * Packs the wind dir speed file.
     *
     * @param DateTime $date
     * @param string   $resolution
     *
     * @throws Throwable
     */
    private function packWindDirSpeed(DateTime $date, string $resolution): void
    {
        $pack = $this->generator->generateWindDS($date, $resolution);

        $path = $this->path->build($date, $resolution, Types::DS);

        $this->moveFile($pack, $path);
    }

    /**
     * Packs the wind UV file.
     *
     * @param DateTime $date
     * @param string   $resolution
     *
     * @throws Throwable
     */
    private function packWindUV(DateTime $date, string $resolution): void
    {
        $pack = $this->generator->generateWindUV($date, $resolution);

        $path = $this->path->build($date, $resolution, Types::UV);

        $this->moveFile($pack, $path);
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
