<?php

namespace GS\Service;

use Symfony\Component\OptionsResolver\{
    Options,
    OptionsResolver
};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use GS\Service\{
    ConfigsService,
    StringService,
    FilesystemService
};

class ClipService
{
    public $contents;
    private $os;

    public function __construct()
    {
        $this->os = \php_uname();
    }

    //###> API ###

    public function copy($contents): void
    {
        $this->contents = \trim($contents);

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
