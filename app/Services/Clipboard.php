<?php

namespace App\Services;

use App\Exceptions\EmptyShareException;
use App\Exceptions\OsUnsupportedException;

class Clipboard
{
    public function setClipboard(string $new): bool
    {
        if (PHP_OS_FAMILY === "Windows") {
            $clip = popen("clip", "wb");
        } elseif (PHP_OS_FAMILY === "Linux") {
            $clip = popen('xclip -selection clipboard', 'wb');
        } elseif (PHP_OS_FAMILY === "Darwin") {
            $clip = popen('pbcopy', 'wb');
        } else {
            throw OsUnsupportedException::supported(['Linux', 'Windows', 'Darwin']);
        }
        $written = fwrite($clip, $new);
        return (pclose($clip) === 0 && strlen($new) === $written);
    }

    public function getClipboard(): ?array
    {
        if (!empty($data = $this->getClipboardImage())) {
            return ['image/png', $data];
        }

        if (!empty($data = $this->getClipboardText())) {
            return ['text/plain', $data];
        }

        throw new EmptyShareException('Your clipboard is empty.');
    }

    private function getClipboardText(): string
    {
        if (PHP_OS_FAMILY === "Linux") {
            return substr(shell_exec('xclip -selection clipboard -o 2>/dev/null'), 0, -1);
        }

        throw OsUnsupportedException::supported(['Linux']);
    }

    private function getClipboardImage(): string
    {
        if (PHP_OS_FAMILY === "Linux") {
            return substr(shell_exec('xclip -selection clipboard -t image/png -o 2>/dev/null'), 0, -1);
        }

        throw OsUnsupportedException::supported(['Linux']);
    }
}
