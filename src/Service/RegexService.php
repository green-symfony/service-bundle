<?php

namespace GS\Service\Service;

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

class RegexService
{
    public function __construct(
        protected readonly string $systemFilePattern,
        protected readonly string $flightReportFileRegex,
        protected readonly string $sdkExtRegex,
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

    public function getEscapedForRegex(
        string $string,
    ): string {
        $string = \strtr(
            $string,
            [
                '|'     => '[|]',
                '+'     => '[+]',
                '*'     => '[*]',
                '?'     => '[?]',
                '['     => '[[]',
                ']'     => '[]]',
                '\\'    => '(?:\\\\|\/)',
                '/'     => '(?:\\|\/)',
                '.'     => '[.]',
                '-'     => '[-]',
                ')'     => '[)]',
                '('     => '[(]',
                '{'     => '[{]',
                '}'     => '[}]',
            ]
        );

        return $string;
    }

    //###< API ###

    //###> HELPER ###

    //###< HELPER ###
}
