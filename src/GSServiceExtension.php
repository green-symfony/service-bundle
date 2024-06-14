<?php

namespace GS\Service;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\DependencyInjection\Definition;
use GS\Service\Configuration;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\{
    YamlFileLoader
};
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use GS\Service\Service\ServiceContainer;
use GS\Service\Service\ConfigService;
use GS\Service\Service\ArrayService;
use GS\Service\Service\BoolService;
use GS\Service\Service\BufferService;
use GS\Service\Service\CarbonService;
use GS\Service\Service\ClipService;
use GS\Service\Service\DumpInfoService;
use GS\Service\Service\FilesystemService;
use GS\Service\Service\HtmlService;
use GS\Service\Service\ParserService;
use GS\Service\Service\RandomPasswordService;
use GS\Service\Service\RegexService;
use GS\Service\Service\StringService;
use GS\Service\Service\OSService;

class GSServiceExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public const PREFIX = 'gs_service';

    public const LOCALE = 'locale';
    protected $localeParameter;
    public const TIMEZONE = 'timezone';
    protected $timezoneParameter;

    public const APP_ENV = 'app_env';
    public const LOCAL_DRIVE_FOR_TEST = 'local_drive_for_test';
    public const FAKER_SERVICE_KEY = 'faker';
    public const CARBON_FACTORY_SERVICE_KEY = 'carbon_factory';

    public const YEAR_REGEX_KEY = 'year_regex';
    public const YEAR_REGEX_FULL_KEY = 'year_regex_full';
    public const IP_V_4_REGEX_KEY = 'ip_v4_regex';
    public const SLASH_OF_IP_REGEX_KEY = 'slash_of_ip_regex';

    public function __construct(
        //private readonly BoolService $boolService,
    ) {
    }

    public function getAlias(): string
    {
        return self::PREFIX;
    }

    /**
        -   (packages).yaml -> containerBuilder
    */
    public function prepend(ContainerBuilder $container)
    {
        $this->loadYaml($container, [
            ['config', 'services.yaml'],
            ['config/packages', 'translation.yaml'],
        ]);
    }

	/**
		-	Configuration -> $config
	*/
    public function getConfiguration(
        array $config,
        ContainerBuilder $container,
    ) {
        return new Configuration(
			locale:     $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::LOCALE),
            ),
            timezone:   $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::TIMEZONE),
            ),
			appEnv: $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::APP_ENV),
            ),
			localDriveForTest: $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::LOCAL_DRIVE_FOR_TEST),
            ),
            gsServiceYearRegex: $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::YEAR_REGEX_KEY),
            ),
            gsServiceYearRegexFull: $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::YEAR_REGEX_FULL_KEY),
            ),
            gsServiceIpV4Regex: $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::IP_V_4_REGEX_KEY),
            ),
            gsServiceSlashOfIpRegex:    $container->getParameter(
                ServiceContainer::getParameterName(self::PREFIX, self::SLASH_OF_IP_REGEX_KEY),
            ),
        );
    }

    /**
        -   (packages).yaml		-> ContainerBuilder
        -   $config				-> global parameters
        -   services($config)	-> global services
        -   bundle's tags
    */
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $this->loadYaml($container, [
            //['config', 'services.yaml'],
        ]);
        $this->setParametersFromBundleConfiguration(
            $config,
            $container,
        );
		$this->createServicesWithConfigArgumentsOfTheCurrentBundle(
            $config,
            $container,
        );
        $this->registerBundleTagsForAutoconfiguration(
            $container,
        );
        $this->setDefinitions(
            $container,
        );
    }

    //###> HELPERS ###

    private function setDefinitions(
        ContainerBuilder $container,
    ): void {
        foreach (
            [
            [
                ArrayService::class,
                ArrayService::class,
            ],
            [
                BoolService::class,
                BoolService::class,
            ],
            [
                BufferService::class,
                BufferService::class,
            ],
            [
                CarbonService::class,
                CarbonService::class,
            ],
            [
                ClipService::class,
                ClipService::class,
            ],
            [
                DumpInfoService::class,
                DumpInfoService::class,
            ],
            [
                FilesystemService::class,
                FilesystemService::class,
            ],
            [
                HtmlService::class,
                HtmlService::class,
            ],
            [
                ParserService::class,
                ParserService::class,
            ],
            [
                RandomPasswordService::class,
                RandomPasswordService::class,
            ],
            [
                RegexService::class,
                RegexService::class,
            ],
            [
                StringService::class,
                StringService::class,
            ],
            [
                OSService::class,
                OSService::class,
            ],
            ] as [ $id, $class ]
        ) {
            $container
                ->setDefinition(
                    $id,
                    (new Definition($class))
                        ->setAutowired(true),
                )
            ;
        }

        foreach (
            [
            [
                StringService::class,
                [
                    '$gsServiceYearRegex' => $container->getParameter(
                        ServiceContainer::getParameterName(
                            self::PREFIX,
                            self::YEAR_REGEX_KEY,
                        ),
                    ),
                    '$gsServiceYearRegexFull' => $container->getParameter(
                        ServiceContainer::getParameterName(
                            self::PREFIX,
                            self::YEAR_REGEX_FULL_KEY,
                        ),
                    ),
                    '$gsServiceIpV4Regex' => $container->getParameter(
                        ServiceContainer::getParameterName(
                            self::PREFIX,
                            self::IP_V_4_REGEX_KEY,
                        ),
                    ),
                    '$gsServiceSlashOfIpRegex' => $container->getParameter(
                        ServiceContainer::getParameterName(
                            self::PREFIX,
                            self::SLASH_OF_IP_REGEX_KEY,
                        ),
                    ),
                ],
            ],
            [
                FilesystemService::class,
                [
                    '$gsServiceLocalDriveForTest' => $container->getParameter(
                        ServiceContainer::getParameterName(self::PREFIX, self::LOCAL_DRIVE_FOR_TEST),
                    ),
                    '$gsServiceAppEnv' => $container->getParameter(
                        ServiceContainer::getParameterName(self::PREFIX, self::APP_ENV),
                    ),
                    '$gsServiceCarbonFactory' => $container->getDefinition(
                        ServiceContainer::getParameterName(self::PREFIX, self::CARBON_FACTORY_SERVICE_KEY),
                    ),
                ],
            ],
            [
                CarbonService::class,
                [
                    '$gsServiceCarbonFactory' => $container->getDefinition(
                        ServiceContainer::getParameterName(self::PREFIX, self::CARBON_FACTORY_SERVICE_KEY),
                    ),
                ],
            ],
            ] as [ $id, $args ]
        ) {
            if ($container->hasDefinition($id)) {
                $container
                    ->getDefinition($id)
                    ->setArguments($args)
                ;
            }
        }
	
		//###> 
		if ($container->hasDefinition(OSService::class)) {
			$container
				->getDefinition(OSService::class)
				->setShared(false)
			;
		}
    }

    private function carbonService(
        array $config,
        ContainerBuilder $container,
    ): void {
        $carbon = new Definition(
            class: \Carbon\FactoryImmutable::class,
            arguments: [
                '$settings'         => [
                    'locale'                    => $this->localeParameter,
                    'strictMode'                => true,
                    'timezone'                  => $this->timezoneParameter,
                    'toStringFormat'            => 'd.m.Y H:i:s P',
                    'monthOverflow'             => true,
                    'yearOverflow'              => true,
                ],
            ],
        );
        $container->setDefinition(
            id: ServiceContainer::getParameterName(self::PREFIX, self::CARBON_FACTORY_SERVICE_KEY),
            definition: $carbon,
        );
    }

    private function fakerService(
        array $config,
        ContainerBuilder $container,
    ): void {
        $faker = (new Definition(\Faker\Factory::class, []))
            ->setFactory([\Faker\Factory::class, 'create'])
            ->setArgument(0, $this->localeParameter)
        ;
        //\dd($config['locale']);
        $faker = $container->setDefinition(
            id: ServiceContainer::getParameterName(self::PREFIX, self::FAKER_SERVICE_KEY),
            definition: $faker,
        );
    }

    private function setParametersFromBundleConfiguration(
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
				return $pa->getValue($config, '[' . $key . ']');
            },
            parameterPrefix: self::PREFIX,
            keys: [
                self::LOCALE,
                self::TIMEZONE,
                self::APP_ENV,
                self::LOCAL_DRIVE_FOR_TEST,
                self::YEAR_REGEX_KEY,
                self::YEAR_REGEX_FULL_KEY,
                self::IP_V_4_REGEX_KEY,
                self::SLASH_OF_IP_REGEX_KEY,
            ],
        );

        ServiceContainer::setParametersForce(
            $container,
            callbackGetValue: static function ($key) use (&$config, $pa) {
                $loadPacksConfigs = [];
                $configsService = $pa->getValue($config, '[' . $key . ']');
                foreach ($configsService as $configService) {
                    /*
                    $packName = $this->boolService->isGet(
                        $configService,
                        ConfigService::PACK_NAME,
                    );
                    $packRelPath = $this->boolService->isGet(
                        $configService,
                        ConfigService::PACK_REL_PATH,
                    );
                    */
                    //###>
                    $packName = null;
                    if (isset($configService[ConfigService::PACK_NAME])) {
                        $packName = $configService[ConfigService::PACK_NAME];
                    }
                    $packRelPath = null;
                    if (isset($configService[ConfigService::PACK_REL_PATH])) {
                        $packRelPath = $configService[ConfigService::PACK_REL_PATH];
                    }
                    $doesNotExistMess = null;
                    if (isset($configService[ConfigService::DOES_NOT_EXIST_MESS])) {
                        $doesNotExistMess = $configService[ConfigService::DOES_NOT_EXIST_MESS];
                    }

                    if ($packName == false) {
                        continue;
                    }
                    if ($packRelPath == false) {
                        $packRelPath = null;
                    }

                    $lazyLoad = $configService[ConfigService::LAZY_LOAD]
                        ?? ConfigService::DEFAULT_LAZY_LOAD
                    ;

                    $loadPacksConfigs [] = [
                        ConfigService::PACK_NAME            => $packName,
                        ConfigService::PACK_REL_PATH        => $packRelPath,
                        ConfigService::LAZY_LOAD            => $lazyLoad,
                        ConfigService::DOES_NOT_EXIST_MESS  => $doesNotExistMess,
                    ];
                }
                return $loadPacksConfigs;
            },
            parameterPrefix: self::PREFIX,
            keys: [
                ConfigService::CONFIG_SERVICE_KEY,
            ],
        );

        /* to use in this object
        $this->localeParameter = new Parameter(ServiceContainer::getParameterName(
            self::PREFIX,
            self::LOCALE,
        ));
        $this->timezoneParameter = new Parameter(ServiceContainer::getParameterName(
            self::PREFIX,
            self::TIMEZONE,
        ));
		*/
    }

    private function createServicesWithConfigArgumentsOfTheCurrentBundle(
        array $config,
        ContainerBuilder $container,
    ) {
        $this->carbonService(
            $config,
            $container,
        );
        $this->fakerService(
            $config,
            $container,
        );
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
