<?php

namespace GS\Service\Service;

use function Symfony\Component\String\u;

use Endroid\QrCode\{
    Builder\Builder,
    Encoding\Encoding,
    ErrorCorrectionLevel\ErrorCorrectionLevelHigh,
    RoundBlockSizeMode\RoundBlockSizeModeMargin,
    Writer\PngWriter
};
use Symfony\Component\HttpFoundation\Response;

final class GSHtmlService
{
    public function __construct()
    {
    }

    public static function getImgHtmlByBinary(
        string $content,
    ): string {
        return (string) u('<img
			class="img-fluid"
			src="data:png;base64,' . \base64_encode($content) . '" alt="img">')->collapseWhitespace();
    }
}
