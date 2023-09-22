<?php

namespace GS\Service\Service;

use function Symfony\Component\String\{
    u,
    b
};

use Symfony\Component\Finder\{
	SplFileInfo,
	Finder
};
use Symfony\Component\Filesystem\{
    Path,
    Filesystem
};
use Symfony\Component\OptionsResolver\{
    Options,
    OptionsResolver
};
use Symfony\Component\Yaml\{
	Tag\TaggedValue,
	Yaml
};
use Symfony\Component\HttpFoundation\{
	Request,
	RequestStack,
	Session\Session
};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use GS\Service\Service\{
	BoolService,
	StringService
};

/**
	This class allows to get some value from package configuration
*/
// as a service only for the sake of autowiring
class ConfigService
{
	public const CONFIG_SERVICE_KEY		= 'load_packs_configs';
	public const PACK_NAME				= 'pack_name';
	public const PACK_REL_PATH			= 'pack_rel_path';
	public const DEFAULT_PACK_REL_PATH	= 'config/packages';
	
	/*
		[
			<PACK_NAME> => [<PACK_CONFIG>],
			...
		]
	*/
	protected array $loadedPackageFilenameData = [];
	
	public function __construct(
		protected readonly BoolService $boolService,
		protected readonly StringService $stringService,
		protected readonly string $projectDir,
		/*
			[
				[
					ConfigService::PACK_NAME		=> <>,
					ConfigService::PACK_REL_PATH	=> ?<>,
				],
			]
		*/
		protected readonly array $packageFilenames,
	) {
		//TODO: remove
		\dd(
			'AUTOWIRING $packageFilenames',
			$packageFilenames,
		);
		$this->initPackageFilenameDataByPackageFilenames();
	}
	
	
	//###> ABSTRACT ###
	
	abstract protected function configureConfigOptions(
		string $uniqPackId,
		OptionsResolver $resolver,
		array $inputData,
	): void;
	
	//###< ABSTRACT ###
	
	
	// ###> API ###
	
	/** USAGE:
	
		$valueFromConfig = $this-> <thisServiceName> -> <thisMethodName> (
			packName:					'workflow',
			propertyAccessString:		'[framework][workflows][order][initial_marking]',
			relPath:					'config/packages',
			...
		);
		
		1) SAVES RESULTS BY <packName><relPath> KEY
			call this method once with unique $packName and $relPath
			it'll save it
	*/
	public function getPackageValue(
		string $packName,
		?string $propertyAccessString = null,
		?string $relPath = null,
		?string $ext = null,
	): mixed {
		$fromLoaded = $this->getConfigFromLoadedPackageFilenameData(
			$packName,
		);
		//###> EXIT POINT
		if (!\is_null($fromLoaded)) {
			return
				\is_null($propertyAccessString)
					? $fromLoaded
					: (PropertyAccess::createPropertyAccessor())->getValue(
						$fromLoaded,
						$propertyAccessString,
					)
				;
		}
		
		//###> LOAD IT ONCE
		$this->setPackageFilenameData(
			$packName,
			$relPath,
			$ext,
		);
		return $this->getPackageValue(
			$packName,
			$propertyAccessString,
		);
	}

    public function getCurrentIp(): string
    {
        return (string) u(\gethostbyname(\gethostname()))->ensureStart('//');
    }
	
	// ###< API ###
	
	
	// ###> HELPER ###
	
	private function getConfigFromLoadedPackageFilenameData(
		string $packName,
	): ?array {
		$configFromLoadedConfig = null;
		
		$filename = $this->getFilenameByPackname($packName);
		
		if (empty($this->loadedPackageFilenameData)) return null;
		
		$config = $this->boolService->isGet($this->loadedPackageFilenameData, $filename);
		if ($config != false) $configFromLoadedConfig = $config;
		
		return $configFromLoadedConfig;
	}
	
	private function getCalculatedConfig(
		string $packName,
		string $ext,
		?string $relPath = null,
	): array {
		$filename = $this->getFilenameByPackname($packName);
		
		$relPath = $this->getDefaultPackRelPathIfNull($relPath);
		
		// Abs path for locator
		$absPath = Path::makeAbsolute(
			$relPath,
			$this->projectDir,
		);
		// abs paths
		$fileLocator = new FileLocator(
			[
				$absPath, // depth: == 0
			]
		);
		
		$resolver = new OptionsResolver();
		
		// Locate and parse Ymal
		$config = \array_replace_recursive(
			...\array_map(
				static fn($path) => Yaml::parseFile(
					Path::canonicalize($path),
					flags: Yaml::PARSE_CUSTOM_TAGS,
				),
				$fileLocator->locate($filename, first: false),
			)
		);
		
		$uniqPackId = $this->getUniqPackId($filename, $relPath);
		$this->configureConfigOptions(
			$uniqPackId,
			$resolver,
			$config,
		);
		return $resolver->resolve($config);
	}
	
	private function initPackageFilenameDataByPackageFilenames(): void {
		//###> init only
		if (!empty($this->packageFilenameData)) return;
		
		//###> without it, it's useless
		if (empty($this->packageFilenames)) return;
		
		foreach($this->packageFilenames as [
			self::PACK_NAME		=> $packName,
			self::PACK_REL_PATH	=> $packRelPath,
		]) {
			$this->setPackageFilenameData(
				$packName,
				$packRelPath,
			);
		}
	}
	
	private function setPackageFilenameData(
		string $packName,
		?string $packRelPath = null,
		?string $ext = null,
	): self {
		$packRelPath = $this->getDefaultPackRelPathIfNull($packRelPath);
		
		$this->loadedPackageFilenameData[$this->getUniqPackId($packName, $packRelPath)] 
			= $this->getCalculatedConfig(
				packName:		$packName,
				ext:			$ext,
				packRelPath:	$packRelPath,
			)
		;
		return $this;
	}
	
	private function getFilenameByPackname(
		string $packName,
	): string {
		//TODO: check correct mime...
		$ext ??= $this->stringService->getExtFromPath($packName) ?? '.yaml';
		return (string) u($packName)->ensureEnd($ext);
	}
	
	/*
		PackId gets with ext
		even if user haven't passed it
	*/
	private function getUniqPackId(
		string $packName,
		?string $packRelPath = null,
	): string {
		$filename = $this->getFilenameByPackname($packName);
		$packRelPath = $this->getDefaultPackRelPathIfNull($packRelPath);
		
		return $this->stringService->getPath($packRelPath, $filename);
	}
	
	private function getDefaultPackRelPathIfNull(
		?string $packRelPath = null,
	): string {
		if (\is_null($packRelPath)) return self::DEFAULT_PACK_REL_PATH;
		
		return $packRelPath;
	}
	
	// ###< HELPER ###
}