<?php

namespace GS\Service\Service;

use Symfony\Component\OptionsResolver\{
	Options,
	OptionsResolver
};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use function Symfony\Component\String\u;

/**
	This class allows to get some value from package .yaml configuration
*/
// as a service only for the sake of autowiring
class GSConfService
{
	public function __construct(
		private readonly string $projectDir,
	) {
	}
	
	// ###> API ###
	
	/**
		$orderInitialMarking	= $this->confService->getPackage(
			filename:					'workflow',
			propertyAccessString:		'[framework][workflows][order][initial_marking]',
		);
	*/
	public function getPackage(
		string $filename,
		string $propertyAccessString,
	): mixed {
		$filename	= (string) u($filename)->ensureEnd('.yaml');
		$config				= $this->getResultConfig(
			$filename,
		);

		return (PropertyAccess::createPropertyAccessor())->getValue($config, $propertyAccessString);
	}
	// ###< API ###
	
	// ###> HELPER ###
	
	private function getResultConfig(
		string $filename,
		string $relPath = 'config/packages',
	): array {
		
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
		$this->configureConfigOptions($resolver, $config);
		return $resolver->resolve($config);
	}
	
	private function configureConfigOptions(OptionsResolver $resolver, array $inputData)
	{
		$resolver
			->setDefaults($inputData)
		;
	}
}