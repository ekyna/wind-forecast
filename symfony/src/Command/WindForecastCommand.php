<?php

namespace App\Command;

use App\Service\Generator;
use App\Util\Resolution;
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
     * @var string
     */
    private $projectDir;

    /**
     * @var Filesystem
     */
    private $filesystem;


    /**
     * Constructor.
     *
     * @param Generator $generator
     * @param string    $projectDir
     */
    public function __construct(Generator $generator, string $projectDir)
    {
        parent::__construct();

        $this->generator = $generator;
        $this->projectDir = $projectDir;
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

        Resolution::validateResolution($resolution);

        $date = new DateTime();

        // 24h - 3h step
        for ($i = 0; $i <= $steps; $i++) {
            // DS
            try {
                $pack = $this->generator->generateWindDS($date, $resolution);
            } catch (Throwable $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");

                return 1;
            }

            $path = sprintf(
                '%s/var/wind/%s/ds/%s.wind',
                $this->projectDir,
                $resolution,
                str_pad($i * 3, 3, '0', STR_PAD_LEFT)
            );

            $this->moveFile($pack, $path);

            // UV
            try {
                $pack = $this->generator->generateWindUV($date, $resolution);
            } catch (Throwable $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");

                return 1;
            }

            $path = sprintf(
                '%s/var/wind/%s/uv/%s.wind',
                $this->projectDir,
                $resolution,
                str_pad($i * 3, 3, '0', STR_PAD_LEFT)
            );

            $this->moveFile($pack, $path);

            $date->modify('+3 hours');
        }

        return 0;
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
