<?php

namespace GS\Service\Service;

use function Symfony\Component\String\{
    u,
    b
};

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceContainer
{
    public function __construct()
    {
    }

    //###> API ###

    /*
        Transforms property accessor syntax into dots:
            prefix[key1][key2]  -> prefix.key1.key2

        prefix.key1.key2    -> prefix.key1.key2
    */
    public static function getParameterName(
        string|int|float|null $prefix,
        string|int|float $key,
    ): string {
        return self::getNormalizedPrefix($prefix) . self::getNormalizedKey($key);
    }

    /**
        @var $keys:
            pass the keys in property accessor syntax:
                [root][child]

            in callbackGetValue:
                PropertyAccess::createPropertyAccessor()->getValue($sourceArray, $key);
    */
    public static function setParametersForce(
        ContainerBuilder $containerBuilder,
        callable|\Closure $callbackGetValue,
        array $keys,
        ?string $parameterPrefix = null,
    ): void {
        foreach ($keys as $key) {
            $containerBuilder->setParameter(
                self::getParameterName($parameterPrefix, $key),
                $callbackGetValue($key)
            );
        }
    }

    /*
        If the parameter has already set, it doesn't set it.
    */
    public static function setParametersNoForce(
        ContainerBuilder $containerBuilder,
        callable|\Closure $callbackGetValue,
        array $keys,
        ?string $parameterPrefix = null,
    ): void {
        $parameterPrefix ??= '';

        foreach ($keys as $key) {
            if (!$containerBuilder->hasParameter($key)) {
                $containerBuilder->setParameter(
                    self::getParameterName($parameterPrefix, $key),
                    $callbackGetValue($key),
                );
            }
        }
    }

    /*
        Remove definitions by ids
    */
    public static function removeDefinitions(
        ContainerBuilder $containerBuilder,
        array $ids,
    ): void {
        foreach ($ids as $id) {
            if ($containerBuilder->hasDefinition($id)) {
                $containerBuilder->removeDefinition($id);
            }
        }
    }

    //###< API ###


    // ###> HELPER ###

    private static function getNormalizedPrefix(
        int|float|string|null $prefix,
    ): string {

        $prefix ??= '';
        if ($prefix != '') {
            $prefix = (string) u($prefix)->ensureEnd('.');
        }

        return $prefix;
    }

    private static function getNormalizedKey(
        int|float|string $key,
    ): string {

        $key        = \strtr((string) $key, [
            '][' => '.',
        ]);

        return \trim($key, '[]');
    }

    // ###< HELPER ###
}
