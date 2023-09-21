<?php

namespace GS\Service;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\DependencyInjection\Definition;
use GS\Service\Configuration;
use Symfony\Component\DependencyInjection\{
	Parameter,
	Reference
};
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\{
    YamlFileLoader
};
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use GS\Service\Service\{
    ServiceContainer,
    BoolService,
    StringNormalizer,
    ConfigService
};

class GSServiceExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public const PREFIX = 'gs_service';
	
	public function __construct(
		private readonly BoolService $boolService,
	)
	public function getAlias(): string
    {
		return self::PREFIX;
	}

    /**
        -   load packages .yaml
    */
    public function prepend(ContainerBuilder $container)
    {
        $this->loadYaml($container, [
            ['config/packages', 'translation.yaml'],
            ['config/packages', 'monolog.yaml'],
        ]);
    }

    public function getConfiguration(
        array $config,
        ContainerBuilder $container,
    ) {
        return new Configuration();
    }

    /**
        -   load services.yaml
        -   config->services
        -   bundle's tags
    */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $this->loadYaml($container, [
            ['config', 'services.yaml'],
        ]);
        $this->fillInParameters($config, $container);
        $this->fillInServiceArgumentsWithConfigOfCurrentBundle($config, $container);
        $this->registerBundleTagsForAutoconfiguration($container);
        /*
        \dd(
            $container->getParameter('gs_generic_parts.timezone'),
        );
        */
    }

    //###> HELPERS ###

    private function fillInParameters(
        array $config,
        ContainerBuilder $container,
    ) {
        /*
        \dd(
            $container->hasParameter('error_prod_logger_email'),
            PropertyAccess::createPropertyAccessor()->getValue($config, '[error_prod_logger_email][from]'),
        );
        */
		
		$pa = PropertyAccess::createPropertyAccessor();
        ServiceContainer::setParametersForce(
            $container,
            callbackGetValue: static function ($key) use (&$config, $pa) {
				$configsServiceResult = [];
				$configsService = $pa->getValue($config, $key);
				foreach($configsService as $configService) {
					foreach($configService as $configServiceEl) {
						$packName = $this->boolService->isGet(
							$configServiceEl,
							ConfigService::PACK_NAME,
						);
						$packRelPath = $this->boolService->isGet(
							$configServiceEl,
							ConfigService::PACK_REL_PATH,
						);
						
						//TODO: 0
						\dd(
							'$packName',
							$packName,
							'$packRelPath',
							$packRelPath,
						);
						if ($packName == false) continue;
						if ($packRelPath == false) {
							$packRelPath = null;
						}
						
						$configsServiceResult []= [
							ConfigService::PACK_NAME =>		$packName,
							ConfigService::PACK_REL_PATH =>	$packRelPath,
						];
					}
				}
                return $configsServiceResult;
            },
            parameterPrefix: self::PREFIX,
            keys: [
				'['.ConfigService::CONFIG_SERVICE_KEY.']',
            ],
        );
		
		/* to use in this object */
		//$this->appEnv = new Parameter(self::APP_ENV);
    }

    private function fillInServiceArgumentsWithConfigOfCurrentBundle(
        array $config,
        ContainerBuilder $container,
    ) {
    }

    private function registerBundleTagsForAutoconfiguration(ContainerBuilder $container)
    {
        /*
        $container
            ->registerForAutoconfiguration(\GS\Service\<>Interface::class)
            ->addTag(GSTag::<>)
        ;
        */
    }

    /**
        @var    $relPath is a relPath or array with the following structure:
            [
                ['relPath', 'filename'],
                ...
            ]
    */
    private function loadYaml(
        ContainerBuilder $container,
        string|array $relPath,
        ?string $filename = null,
    ): void {

        if (\is_array($relPath)) {
            foreach ($relPath as [$path, $filename]) {
                $this->loadYaml($container, $path, $filename);
            }
            return;
        }

        if (\is_string($relPath) && $filename === null) {
            throw new \Exception('Incorrect method arguments');
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(
                [
                    __DIR__ . '/../' . $relPath,
                ],
            ),
        );
        $loader->load($filename);
    }
}
