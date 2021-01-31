<?php

namespace App;

class Helper
{
    public static function cleanDirectory(string $dir): float
    {
        $size = 0;

        $structure = glob(rtrim($dir, "/") . '/{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
        if (is_array($structure)) {
            foreach ($structure as $file) {
                if (in_array($file, ['..', '.'])) {
                    continue;
                } elseif (is_dir($file)) {
                    $size += self::cleanDirectory($file);
                } elseif (is_file($file)) {
                    $size += filesize($file);
                    unlink($file);
                }
            }
        }

        rmdir($dir);

        return $size;
    }

    public static function formatFilesize(float $size): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public static function getVendor(string $vendor): array
    {
        switch ($vendor) {
            case 'npm':
            case 'node':
            case 'nodejs':
            case 'node_modules':
                return ['node_modules', 'package.json'];
            case 'php':
            case 'composer':
            default:
                return ['vendor', 'composer.json'];
        }
    }
}
