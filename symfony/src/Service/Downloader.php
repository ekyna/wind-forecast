<?php

namespace App\Service;

use App\Util\DateUtil;
use App\Util\Resolution;
use DateTime;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class Downloader
 * @package App\Service
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Downloader
{
    // https://nomads.ncep.noaa.gov/pub/data/nccf/com/gfs/prod/gfs.20201212/18/gfs.t18z.pgrb2.1p00.f384
    private const URL = 'https://nomads.ncep.noaa.gov/pub/data/nccf/com/gfs/prod/gfs.%s/%s/gfs.t%sz.pgrb2.%s.f%s';

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $resolution;

    /**
     * @var DateTime
     */
    private $cycle;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    /**
     * Downloads the grib file.
     *
     * @param DateTime $date
     * @param string   $resolution
     *
     * @return string The downloaded file path
     *
     * @throws RuntimeException
     */
    public function download(DateTime $date, string $resolution): string
    {
        Resolution::validate($resolution);

        $this->resolution = $resolution;

        $this->resolveCycle();

        if (!DateUtil::checkStep($date, 3)) {
            throw new RuntimeException("Unexpected date time.");
        }

        // Calculate offset
        $diff = $this->cycle->diff($date);
        $offset = 24 * $diff->days + $diff->h;

        $url = $this->buildUrl($offset);

        // First try
        try {
            $response = $this->client->request('GET', $url);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new RuntimeException("Failed to download $url");
            }
        } catch (TransportExceptionInterface $e) {
            // Second try
            $this->cycle->modify('-6 hours');
            $url = $this->buildUrl($offset);

            try {
                $response = $this->client->request('GET', $url);
                if (Response::HTTP_OK !== $response->getStatusCode()) {
                    throw new RuntimeException("Failed to download $url");
                }
            } catch (TransportExceptionInterface $e) {
                throw new RuntimeException("Failed to download $url");
            }
        }

        // Write stream
        $path = tempnam(sys_get_temp_dir(), 'wind_');
        $handler = fopen($path, 'w');
        try {
            foreach ($this->client->stream($response) as $chunk) {
                fwrite($handler, $chunk->getContent());
            }
        } catch (TransportExceptionInterface $e) {
            fclose($handler);

            throw new RuntimeException("Failed to write stream from $url");
        }

        fclose($handler);

        return $path;
    }

    /**
     * Resolves the cycle base date.
     */
    private function resolveCycle(): void
    {
        if ($this->cycle) {
            return;
        }

        $this->cycle = DateUtil::roundStep(null, 6);

        $attempt = 8;
        while (0 < $attempt) {
            $attempt--;

            $url = $this->buildUrl(0);

            try {
                $response = $this->client->request('HEAD', $url);
                if (Response::HTTP_OK === $response->getStatusCode()) {
                    return;
                }
            } catch (TransportExceptionInterface $e) {
            }

            if (0 === $attempt) {
                throw new RuntimeException("Failed to resolve cycle date.");
            }

            $this->cycle->modify('-6 hours');
        }
    }

    /**
     * Builds the remote grib file URL.
     *
     * @param int $offset
     *
     * @return string
     */
    private function buildUrl(int $offset): string
    {
        return sprintf(
            self::URL,
            $this->cycle->format('Ymd'),
            $h = $this->cycle->format('H'),
            $h,
            $this->resolution,
            str_pad($offset, 3, '0', STR_PAD_LEFT)
        );
    }
}
