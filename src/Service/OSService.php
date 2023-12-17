<?php

namespace GS\Service\Service;

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

class OSService
{
    protected array $store;
    protected $currentOs;

    public function __construct()
    {
        $this->currentOs = \php_uname(mode: "s");
    }

    //###> API ###

	/*
		Invoke it to execute the callback
		for the connected Operation System
	*/
	public function __invoke(
		string|int $callbackKey,
		bool $removeCallbackAfterExecution,
		...$args,
	): mixed {
		foreach($this->store as $requiredOs => $callbacks) {
			if (\preg_match('~' . $requiredOs . '~i', $this->currentOs)) {
				foreach($callbacks as $storeCallbackKey => $callback) {
					if ($storeCallbackKey === $callbackKey) {
						if ($removeCallbackAfterExecution) {
							unset($this->store[$requiredOs][$storeCallbackKey]);							
						}
						return $callback(...$args);
						break;
					}
				}
				break;
			}
		}
		return null;
    }

	/*
		Return value of the callback $getOsName must be ?string that will be compared with $this->currentOs
		for different Operation Systems
			Windows
			Darwin
			Linux
			FreeBSD
			...
		
		__invoke returns What returns callback
	*/
	public function setCallback(
		callable|\Closure $getOsName,
		string|int $callbackKey,
		callable|\Closure $callback,
	): static {
		$os = $getOsName();
		
		if (!\is_string($os)) {
			if (\is_null($os)) {
				return $this;
			}
			throw new \Exception(
				'Return value of the callback $getOsName must be ?string that will be compared with $this->currentOs'
			);
		}
		
		$this->store[$os][$callbackKey] = $callback;
		
		return $this;
	}

    //###< API ###
}
