<?php

namespace GS\Service\Service;

class ArrayService
{
    //###> API ###

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

    public function getMaxLen(array $input): int
    {
        return \max(\array_map(static fn($v) => \mb_strlen((string) $v), $input));
    }

    public function throwIfNotItemsType(
        \Traversable $items,
        string $type,
    ): void {
        $this->isItemsMeasureUpToType(
            items:          $items,
            type:           $type,
            throw:          true,
        );
    }

    public function isItemsType(
        \Traversable $items,
        string $type,
    ): bool {
        return $this->isItemsMeasureUpToType(
            items:          $items,
            type:           $type,
            throw:          false,
        );
    }

    //###< API ###


    //###> HELPER ###

    /*
        Are all the items measure up to type
    */
    private function isItemsMeasureUpToType(
        \Traversable $items,
        string $type,
        bool $throw,
    ): bool {
        $measureUpTypes = true;

        foreach ($items as $item) {
            $measureUpTypes = $measureUpTypes && (\is_object($item)
                ? $item instanceof $type
                : gettype($item) === $type
            );
            if ($measureUpTypes === false) {
                break;
            }
        }

        if ($throw && !$measureUpTypes) {
            throw new \Exception('Не все элементы массива типа: "' . $type . '"');
        }

        return $measureUpTypes;
    }

    //###< HELPER ###
}
