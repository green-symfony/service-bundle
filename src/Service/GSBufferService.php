<?php

namespace GS\Service\Service;

use Endroid\QrCode\{
    Builder\Builder,
    Encoding\Encoding,
    ErrorCorrectionLevel\ErrorCorrectionLevelHigh,
    RoundBlockSizeMode\RoundBlockSizeModeMargin,
    Writer\PngWriter
};
use Symfony\Component\HttpFoundation\Response;

final class GSBufferService
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
