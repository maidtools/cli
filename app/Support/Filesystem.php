<?php

namespace App\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Inspired by: https://github.com/FriendsOfShopware/FroshTools/blob/main/src/Components/CacheHelper.php
 */
class Filesystem
{
    public static function deleteDirectory(string $dir): void
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            self::deleteWindowsDirectory($dir);

            return;
        }

        self::deleteLinuxDirectory($dir);
    }

    private static function rsyncAvailable(): bool
    {
        $output = null;
        exec('command -v rsync', $output);

        return $output !== null && count($output) > 0;
    }

    private static function deleteWindowsDirectory(string $dir): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $todo = $file->isDir() ? 'rmdir' : 'unlink';
            $todo($file->getRealPath());
        }

        rmdir($dir);
    }

    private static function deleteLinuxDirectory(string $dir): void
    {
        if (self::rsyncAvailable()) {
            $blankDir = sys_get_temp_dir() . '/' . md5($dir . time()) . '/';

            if (!mkdir($blankDir, 0777, true) && !is_dir($blankDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $blankDir));
            }

            exec('rsync -a --delete ' . $blankDir . ' ' . $dir . '/');
            rmdir($blankDir);
        } else {
            exec('find ' . $dir . '/ -delete');
        }
    }
}
