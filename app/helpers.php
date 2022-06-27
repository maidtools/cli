<?php

if (!function_exists('maid_cli_path')) {
    function maid_cli_path(): string
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $result = exec("echo %appdata%");
        } else {
            $result = getenv('HOME') . DIRECTORY_SEPARATOR . '.config';
        }

        return $result . DIRECTORY_SEPARATOR . 'maid';
    }
}
