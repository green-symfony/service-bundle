<?php

namespace GS\Service\Service;

use function Symfony\Component\String\{
    u,
    b
};

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
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

class RegexService
{
    public function __construct(
        private readonly string $systemFilePattern,
        private readonly string $flightReportFileRegex,
        private readonly string $sdkExtRegex,
    ) {
    }

    //###> API ###

    public function getNotTmpDocxRegex(): string
    {
        return '~^' . $this->systemFilePattern . '.*[.]docx?$~ui';
    }

    public function getFlightReportFileRegex(): string
    {
        return '~^' . $this->flightReportFileRegex . '$~ui';
    }

    public function getSdkFileRegex(): string
    {
        return '~^(?<boardNumber>[0-9]{4})[_](?<day>[0-9]{2})(?<month>[0-9]{2})(?<fullYear>[0-9]{4})[_][0-9]{3,}' . $this->sdkExtRegex . '$~i';
    }

    //###< API ###

    //###> HELPER ###

    //###< HELPER ###
}
