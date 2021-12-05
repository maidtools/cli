<?php

namespace App;

use Symfony\Component\Yaml\Yaml;

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

    public static function getK8sDir(): string
    {
        return base_path('k8s');
    }

    public static function getK8sEnvironment(): array
    {
        return Yaml::parseFile(
            sprintf('%s%sk8s.yml', self::getK8sDir(), DIRECTORY_SEPARATOR)
        );
    }

    public static function saveFile(string $filename, string $content)
    {
        $parentDirectory = dirname($filename);
        if (!is_dir($parentDirectory)) {
            mkdir($parentDirectory, 0755, true);
        }
        file_put_contents($filename, $content);
    }

    public static function dump(array $array)
    {
        return preg_replace('/-\n\s+/', '- ', Yaml::dump($array, 512, 2));
    }

    public static function dumpK8sFile(array $array)
    {
        self::saveFile('k8s/k8s.yml', self::dump($array));
    }

    // get the contents of all files and merge them with ---
    public static function mergeYamlFiles(array $serviceFiles): string
    {
        $serviceContent = '';

        foreach ($serviceFiles as $serviceFile) {
            $serviceContent .= file_get_contents($serviceFile) . "\n---\n";
        }

        return preg_replace('/\n+/', "\n", $serviceContent);
    }

    public static function isCommandAvailable(string $string): bool
    {
        $command = sprintf('which %s', $string);
        return trim(shell_exec($command)) !== '';}

    public static function installCommand(string $string, bool $snap = false)
    {
        if ($snap) {
            $command = sprintf('sudo snap install %s --classic', $string);
        } else {
            $command = sprintf('sudo apt-get install %s', $string);
        }
        shell_exec($command);
    }
}
