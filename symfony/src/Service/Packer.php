<?php

namespace App\Service;

use RuntimeException;

/**
 * Class Packer
 * @package App\Service
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Packer
{
    /**
     * @param string $uPath
     * @param string $vPath
     *
     * @return string
     */
    public function packUV(string $uPath, string $vPath): string
    {
        $uData = $this->readBinary($uPath);
        $vData = $this->readBinary($vPath);

        $data = $this->pack('ss', $uData, $vData, 100);

        if (false === $pPath = tempnam(sys_get_temp_dir(), 'wuv_')) {
            throw new RuntimeException("Failed to create wind pack file.");
        }

        $this->writeBinary($pPath, $data);

        return $pPath;
    }

    /**
     * Packs the wind direction and speed data into a binary file.
     *
     * @param string $dPath The direction data binary file path.
     * @param string $sPath The speed data binary file path.
     *
     * @return string
     */
    public function packDirSpeed(string $dPath, string $sPath): string
    {
        $dData = $this->readBinary($dPath);
        $sData = $this->readBinary($sPath);

        $data = $this->pack('SS', $dData, $sData, 100);

        if (false === $pPath = tempnam(sys_get_temp_dir(), 'wds_')) {
            throw new RuntimeException("Failed to create wind pack file.");
        }

        $this->writeBinary($pPath, $data);

        return $pPath;
    }

    /**
     * Packs the 2 arrays.
     *
     * @param string   $format
     * @param array    $a
     * @param array    $b
     * @param int|null $factor
     *
     * @return string
     */
    private function pack(string $format, array $a, array $b, int $factor = null): string
    {
        if ($factor) {
            $apply = function (float $f) use ($factor) {
                return (int)($f * 100);
            };
            $a = array_map($apply, $a);
            $b = array_map($apply, $b);
        }

        $pack = '';
        for ($i = 0; $i < count($a); $i++) {
            $pack .= pack($format, $a[$i], $b[$i]);
        }

        return $pack;
    }

    /**
     * Reads binary data from the given file.
     *
     * @param string $path The file path
     *
     * @return float[]
     */
    private function readBinary(string $path): array
    {
        if (false === $handle = fopen($path, 'rb')) {
            throw new RuntimeException("Failed to open $path file for reading.");
        }

        $data = fread($handle, filesize($path));

        if (false === fclose($handle)) {
            throw new RuntimeException("Failed to close $path file.");
        }

        if (false === $data) {
            throw new RuntimeException("Failed to read from $path file.");
        }

        $data = array_values(unpack('f*', $data));

        array_shift($data); // Remove headers
        array_pop($data); // Remove empty value

        return $data;
    }

    /**
     * Writes the binary data into the given file.
     *
     * @param string $path The file path
     * @param string $data The binary data
     */
    private function writeBinary(string $path, string $data): void
    {
        if (false === $handle = fopen($path, 'wb')) {
            throw new RuntimeException("Failed to open $path file for writing.");
        }

        for ($length = 0; $length < strlen($data); $length += $tmp) {
            $tmp = fwrite($handle, substr($data, $length));
            if ($tmp === false) {
                throw new RuntimeException("Failed to write into $path file.");
            }
        }

        if (!fclose($handle)) {
            throw new RuntimeException("Failed to close $path");
        }
    }
}
