<?php

namespace GS\Service;

class ArrayService
{
    //###> API ###

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

    /* for AbstractPatternAbleConstructedFromToCommand::useParserYearMonthBoardNumber
        gets the first result

        Если в $primaryKeysValues указать всё, что только возможно будет в $pattern
            можно гарантировать возврат [] при совпадении

		forFilter: false (for constructing aims)
			returns pattern diff array when there were interesections
			RETURNS [] WHEN THERE IS NO DIFFERENCE BETWEEN PATTERN DATA AND PASSED ONES
			RETURNS null when wasn't intersection passed $primaryKeysValues with passed $patterns
			Если $pattern имеет какие-то различия от $primaryKeysValues возвращает не пустой массив
		forFilter: true (for filtering aims)
			returns [] when pattern was matched
			returns null when pattern was NOT matched
    */
    public function getParsedFromYearMonthBoardNumber(
        array $primaryKeysValues,
        array $patterns,
        bool $forFilter = false,
    ): ?array {
        foreach ($patterns as $pattern) {
			$diff = \array_diff_assoc($pattern, $primaryKeysValues);
			//###> Цель forFilter: вернуть [] если это возможно (означает полное совпадение с $pattern)
			if ($forFilter) {
				if (empty($diff)) return [];
				continue;
			}
            // Были хоть какие-то совпадения?
            $result = \array_intersect_assoc($primaryKeysValues, $pattern);
			//###> Верни разницу относительно $pattern ([] всё так же как и в $pattern)
			if (!empty($result)) return $diff;
        }
        return null;
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
