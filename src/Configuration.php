<?php

namespace GS\Command;

use function Symfony\Component\String\u;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use GS\Command\GSCommandExtension;

class Configuration implements ConfigurationInterface
{
    public function __construct(
        private readonly string $appEnv,
    ) {
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(GSCommandExtension::PREFIX);

        $treeBuilder->getRootNode()
            ->children()
			
                ->scalarNode('env(APP_ENV)')
                    ->info('env(APP_ENV)')
                    #->defaultValue('%gs_generic_parts.locale%') Don't work, it's a simple string if defaultValue
                    ->defaultValue($this->appEnv)
                ->end()

            ->end()
        ;

        //$treeBuilder->setPathSeparator('/');

        return $treeBuilder;
    }

    //###> HELPERS ###
}
