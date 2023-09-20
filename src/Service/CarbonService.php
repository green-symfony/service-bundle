<?php

namespace GS\Service;

use Carbon\{
    Carbon,
    Factory,
    FactoryImmutable,
    CarbonImmutable
};
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
use App\Entity\{
    AbstractUser,
    OrderItem,
    Order
};
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Repository\{
    DiscountRepository,
    OrderRepository
};
use GS\Service\{
    ConfigsService,
    FilesystemService,
    DumpInfoService,
    StringService,
    ParametersService
};

class CarbonService
{
    public function __construct(
        private $carbonFactory,
    ) {
    }

    //###> API ###

    public function getCurrentYear(): string|int
    {
        $carbon                 = $this->carbonFactory->make(
            Carbon::now('UTC'),
        );
        return $carbon->year;
    }

    /* MMMM month as a word */
    public function getCurrentMonthWord(): string
    {
        return $this->carbonFactory
            ->make(Carbon::now('UTC'))
            ->isoFormat('MMMM')
        ;
    }

    /* MMMM month as a word */
    public function getNextMonthWord(): string
    {
        return $this->carbonFactory
            ->make(Carbon::now('UTC'))
            ->addMonthsNoOverflow(1)
            ->isoFormat('MMMM')
        ;
    }

    /*
        01-12

        May throw \Exception
    */
    public function getMonthWordByNumber(
        int|string $monthNumber,
    ): string {
        $monthWord = null;

        try {
            $carbon = $this->carbonFactory
                ->make(Carbon::now('UTC'))
                ->month($monthNumber)
            ;
        } catch (\Exception $e) {
            throw new \Exception('Не корректное значение числа месяца для ' . Carbon::class . ': ' . $monthNumber);
        }

        $monthWord = $carbon->isoFormat('MMMM');

        if ($monthWord === null) {
            throw new \Exception('Месяц не распознан из номера месяца: ' . $monthNumber);
        }

        return $monthWord;
    }

    //###< API ###
}
