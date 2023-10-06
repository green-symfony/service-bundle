<?php

namespace GS\Service\Service;

class StringNormalizer
{
    //###> API ###

    public static function getFullLocale(
        string $locale,
    ): string {
        if (!\str_contains($locale, '_')) {
            $locale = \strtolower($locale) . '_' . \strtoupper($locale);
        }
        return $fullLocale;
    }

    //###< API ###
}
