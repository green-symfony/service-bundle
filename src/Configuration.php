<?php

namespace GS\Service;

use function Symfony\Component\String\u;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use GS\Service\{
	GSServiceExtension
};
use GS\Service\Service\{
	ConfigService
};

class Configuration implements ConfigurationInterface
{
    public function __construct(
    ) {
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(GSServiceExtension::PREFIX);

		$treeBuilder->getRootNode()
            ->children()
			
				//###> ConfigService TODO: 0
                ->arrayNode(ConfigService::CONFIG_SERVICE_KEY)
					->arrayPrototype()
					->defaultValue([])
						->children()
							->scalarNode(ConfigService::PACK_NAME)
								->info('it\'s a name of the pack with or without .yaml extension')
								->cannotBeEmpty()
							->end()
							->scalarNode(ConfigService::PACK_REL_PATH)
								->info('it\'s a relative path of pack file')
								->defaultValue(ConfigService::DEFAULT_PACK_REL_PATH)
							->end()
						->end()
					->end()
				->end()

            ->end()
        ;
		
        //$treeBuilder->setPathSeparator('/');

        return $treeBuilder;
    }

    //###> HELPERS ###
}
