<?php

namespace GS\Service\Service;

use Carbon\{
    Carbon,
    CarbonImmutable
};

use function Symfony\Component\String\u;

use GS\Service\Contracts\{
    GSIsoFormat
};
use GS\Service\IsoFormat\{
    GSLLLIsoFormat
};

class GSCarbonService
{
    public static function isoFormat(
        Carbon|CarbonImmutable $carbon,
        ?GSIsoFormat $isoFormat = null,
        bool $isTitle = true,
    ): string {
        $isoFormat  ??= new GSLLLIsoFormat();
        $tz         = $carbon->tz;

        return (string) u($carbon->isoFormat($isoFormat::get()) . ' [' . $tz . ']')->title($isTitle);
    }

    public static function forUser(
        Carbon|CarbonImmutable $origin,
        \DateTimeImmutable|\DateTime $sourceOfMeta = null,
        ?string $tz = null,
        ?string $locale = null,
    ): Carbon|CarbonImmutable {
        $carbonClone            = ($origin instanceof Carbon) ? $origin->clone() : $origin;
        return $sourceOfMeta ?
            $carbonClone->tz($sourceOfMeta->tz)->locale($sourceOfMeta->locale) :
            $carbonClone->tz($tz ?? $carbonClone->tz)->locale($locale ?? $carbonClone->locale)
        ;
    }
}
