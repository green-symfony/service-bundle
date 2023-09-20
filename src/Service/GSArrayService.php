<?php

namespace GS\Service\Service;

class GSArrayService
{
    public static function getKeyValueString(
        array $keyValue,
        string $separator = ', ',
        bool $considerAlphaKyesOnly = true,
    ): string {
        $params = [];
        \array_walk($keyValue, static function ($v, $k) use (&$params, &$considerAlphaKyesOnly) {
            if ($considerAlphaKyesOnly && \is_int($k)) {
                return $params[] = $v;
            }
            $params[] = $k . ': ' . $v;
        });

        return \implode($separator, $params);
    }
}
