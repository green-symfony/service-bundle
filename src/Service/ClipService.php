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

class ClipService
{
    public string $contents;
    private $os;

    public function __construct()
    {
        $this->os = \php_uname();
    }

    //###> API ###

    public function copy(int|float|string|null $contents): void
    {
		if ($contents === null) return;
		
        $this->contents = \trim((string) $contents);

        if (\preg_match('~windows~i', $this->os)) {
            $this->windows();
            return;
        }
        if (\preg_match('~darwin~i', $this->os)) {
            $this->mac();
            return;
        }
        $this->linux();
    }

    //###< API ###

    //###> HELPER ###

    private function mac(): void
    {
        \exec('echo ' . $this->contents . ' | pbcopy');
    }

    private function linux(): void
    {
        \exec('echo ' . $this->contents . ' | xclip -sel clip');
    }

    private function windows(): void
    {
        \exec('echo | set /p="' . $this->contents . '" | clip');
    }

    //###< HELPER ###
}
