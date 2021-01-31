<?php

namespace App;

class Helper
{
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

    public static function ignored($cwd = null, $ignored = ['..', '.']): array
    {
        $cwd = $cwd ?? getcwd();
        $ignoreFile = sprintf('%s%s.maidignore', $cwd, DIRECTORY_SEPARATOR);

        if (is_file($ignoreFile)) {
            $ignored = array_merge($ignored, array_filter(explode(PHP_EOL, file_get_contents($ignoreFile))));
        }

        return $ignored;
    }
}
