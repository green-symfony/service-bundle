<?php

namespace GS\Service\Service;

final class BufferService
{
    public function __construct()
    {
    }

    /**
        Works with php output buffer
    */
    public static function clear(): void
    {
        while (\ob_get_level()) {
            \ob_end_clean();
        }
    }
}
