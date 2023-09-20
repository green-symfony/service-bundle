<?php

namespace GS\Service;

use function Symfony\Component\String\{
    u,
    b
};

use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\{
    Options,
    OptionsResolver
};
use Symfony\Component\Filesystem\{
    Path,
    Filesystem
};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\{
    AbstractUser,
    OrderItem,
    Order
};
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use GS\Service\{
    CarbonService,
    PartOfPathService,
    ConfigsService,
    ArrayService
};

class BoolService
{
    public function __construct()
    {
    }

    //###> API ###

    /*
        If key is exists in array and it isn't false value, give it!
    */
    public function isGet(
        array $array,
        string $key,
    ): bool|string {
        return (isset($array[$key]) && $array[$key] != false/* not !== */)
            ? $array[$key]
            : false
        ;
    }
	
	public function isCurrentConsolePathStartsWithSlash(): bool {
		return \str_starts_with(Path::normalize(\getcwd()), '/');
	}

    //###< API ###

    //###> HELPER ###

    //###< HELPER ###
}
