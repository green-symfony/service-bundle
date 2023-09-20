<?php

namespace GS\Service\Service;

class GSClipService
{
    public static function copy(string|float|int $contents): void
    {
		$os				= \php_uname();
		
        $contents		= \trim((string) $contents);

        if (\preg_match('~windows~i', $os)) {
            $this->windows($contents);
            return;
        }
        if (\preg_match('~darwin~i', $os)) {
            $this->mac($contents);
            return;
        }
        $this->linux($contents);
    }

    // ###> HELPER ###

    private function mac($contents): void
    {
        \exec('echo ' . $contents . ' | pbcopy');
    }

    private function linux($contents): void
    {
        \exec('echo ' . $contents . ' | xclip -sel clip');
    }

    private function windows($contents): void
    {
        \exec('echo | set /p="' . $contents . '" | clip');
    }
}
