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
        private $locale,
        private $timezone,
    ) {
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(GSServiceExtension::PREFIX);

        $treeBuilder->getRootNode()
            ->info(''
                . 'You can copy this example: "'
                . \dirname(__DIR__)
                . DIRECTORY_SEPARATOR . 'config'
                . DIRECTORY_SEPARATOR . 'packages'
                . DIRECTORY_SEPARATOR . 'gs_service.yaml'
                . '"')
            ->children()

                ->scalarNode(GSServiceExtension::LOCALE)
                    ->isRequired()//->defaultValue($this->locale)
                    ->info('Locale for services')
                    //->defaultValue('%gs_generic_parts.locale%') Don't work, it's a simple string if defaultValue
                ->end()

                ->scalarNode(GSServiceExtension::TIMEZONE)
                    ->isRequired()//->defaultValue($this->timezone)
                    ->info('Timezone for services')
                ->end()

                ->scalarNode(GSServiceExtension::APP_ENV)
                    ->isRequired()
                ->end()

                ->scalarNode(GSServiceExtension::LOCAL_DRIVE_FOR_TEST)
                    ->isRequired()
                ->end()

                ->arrayNode(ConfigService::CONFIG_SERVICE_KEY)
                    ->info('the packs whose config will be loaded when GS\\Service\\Service\\ConfigService creates')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode(ConfigService::PACK_NAME)
                                ->info('it\'s a name of the pack with or without the .yaml extension')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode(ConfigService::PACK_REL_PATH)
                                ->info('it\'s a relative path of the pack file')
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
