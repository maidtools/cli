<?php

namespace App;

use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class K8s
{
    public static function runCommand(string $string, Commands\K8sApplyCommand $param)
    {
        $process = Process::fromShellCommandline($string);
        $process->setTimeout(300);
        $process->setIdleTimeout(300);
        $process->run(function ($type, $buffer) use ($param) {
            if (Process::ERR === $type) {
                $param->error(trim($buffer));
            } else {
                $param->info(trim($buffer));
            }
        });
    }

    /**
     * Returns the k8s.yaml configuration file.
     */
    public static function config(): array
    {
        return Yaml::parseFile(self::base('k8s.yaml'));
    }

    /**
     * Returns the base path of the k8s directory, if a $filename is given, append this filename.
     */
    public static function base(?string $filename): string
    {
        $base = getcwd() . '/k8s';

        if ($filename) {
            $base .= '/' . $filename;
        }

        return $base;
    }

    /**
     * Create a new yaml file within the k8s directory.
     */
    public static function yaml(string $filename, array $array): void
    {
        $content = preg_replace('/-\n\s+/', '- ', Yaml::dump($array, 512, 2));

        file_put_contents(self::ensureParentDirectoryExits(self::base($filename)), $content);
    }

    /**
     * Merge multiple files into one. Separate files with a ---.
     * The file will be saved within the k8s directory.
     */
    public static function merge(string $filename, array $files)
    {
        $content = '';
        foreach ($files as $file) {
            $content .= file_get_contents(self::base($file)) . "\n---\n";
        }

        $content = preg_replace('/\n+/', "\n", $content);

        file_put_contents(self::ensureParentDirectoryExits(self::base($filename)), $content);
    }

    public static function cleanup(array $helperFiles)
    {
        foreach ($helperFiles as $helperFile) {
            unlink(self::base($helperFile));
        }
    }

    public static function ensureParentDirectoryExits(string $base): string
    {
        $dir = dirname($base);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $base;
    }

    public function copyStub(Command $command, string $name): bool
    {
        if ($name === 'redis-cluster') {
            $attributes = [
                'master_password' => Str::random(32),
                'password' => Str::random(32),
                'disabled_commands' => 'FLUSHDB,FLUSHALL',
                'secondary_replicas' => 2,
            ];
            $attributes = $this->askForAttributes($command, $attributes);
            $this->copyStubTemplate($command, 'redis-primary', $attributes);
            $this->copyStubTemplate($command, 'redis-secondary', $attributes);
        } else {
            return false;
        }

        // print all attributes as a table
        $this->printAttributes($attributes);

        return true;
    }

    private function copyStubTemplate(Command $command, string $name, array $attributes)
    {
        // get all files from stubs/k8s/templates/$name directory and copy them to k8s/templates/$name directory
        $files = glob(__DIR__ . '/../stubs/k8s/templates/' . $name . '/*');
        foreach ($files as $file) {
            $filename = basename($file);
            $targetFilename = self::ensureParentDirectoryExits(
                self::base('templates/' . $name . '/' . $filename)
            );

            if(file_exists($targetFilename)) {
                $command->error('File ' . $targetFilename . ' already exists.');
                return;
            }

            copy(
                __DIR__ . '/../stubs/k8s/templates/' . $name . '/' . $filename,
                $targetFilename
            );

            $k8s = self::config();

            // replace the placeholders in the template file
            $content = file_get_contents($targetFilename);
            $content = str_replace('{{namespace}}', $k8s['namespace'], $content);

            foreach ($attributes as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }


            file_put_contents($targetFilename, $content);

        }
    }

    private function printAttributes(array $attributes)
    {
        $headers = ['Attribute', 'Value'];
        $table = new \cli\Table();
        $table->setHeaders($headers);
        foreach ($attributes as $key => $value) {
            $table->addRow([$key, $value]);
        }
        $table->display();
    }

    private function askForAttributes(Command $command, array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = $command->ask('Enter ' . $key . ': ', $value);
        }

        return $attributes;
    }
}
