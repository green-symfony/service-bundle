<?php

namespace GS\Service\Service;

class Parser
{
    public static function getFirstNameLastNamePatronymic(string $fullName): array
    {
		$matches = [];
		
		\preg_match('~^([a-zа-я]*)\s*([a-zа-я]*)\s*([a-zа-я]*)\s*$~iu', $fullName, $matches);
		\array_walk($matches, static fn(&$v) => $v = \trim($v));
		
		$firstName		= null;
		$lastName		= null;
		$patronymic		= null;
		
		foreach([
			[ &$firstName, 1 ],
			[ &$lastName, 2 ],
			[ &$patronymic, 3 ],
		] as [ &$propertyRef, $groupNumber ]) {
			if (isset($matches[$groupNumber]) && $matches[$groupNumber] !== '') {
				$propertyRef = $matches[$groupNumber];
			}
		}
		
		return [$firstName, $lastName, $patronymic];
    }
}
