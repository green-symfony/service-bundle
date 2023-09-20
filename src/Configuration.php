<?php

namespace GS\Service;

use function Symfony\Component\String\u;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use GS\Service\Service\GSCommandExtension;

class Configuration implements ConfigurationInterface
{
    public function __construct(
    ) {
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(GSCommandExtension::PREFIX);

        //$treeBuilder->setPathSeparator('/');

        return $treeBuilder;
    }

    //###> HELPERS ###
}
