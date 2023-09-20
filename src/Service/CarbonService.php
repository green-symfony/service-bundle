<?php

namespace GS\Service\Service;

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
use GS\Service\Contracts\{
    GSIsoFormat
};
use GS\Service\IsoFormat\{
    GSLLLIsoFormat
};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Service\Attribute\Required;

class CarbonService
{
    public function __construct(
        private $carbonFactory,
    ) {
    }

    //###> API ###
	
    public static function isoFormat(
        Carbon|CarbonImmutable $carbon,
        ?GSIsoFormat $isoFormat = null,
        bool $isTitle = true,
    ): string {
        $isoFormat  ??= new GSLLLIsoFormat();
        $tz         = $carbon->tz;

        return (string) u($carbon->isoFormat($isoFormat::get()) . ' [' . $tz . ']')->title($isTitle);
    }

    public static function forUser(
        Carbon|CarbonImmutable $origin,
        \DateTimeImmutable|\DateTime $sourceOfMeta = null,
        ?string $tz = null,
        ?string $locale = null,
    ): Carbon|CarbonImmutable {
        $carbonClone            = ($origin instanceof Carbon) ? $origin->clone() : $origin;
        return $sourceOfMeta ?
            $carbonClone->tz($sourceOfMeta->tz)->locale($sourceOfMeta->locale) :
            $carbonClone->tz($tz ?? $carbonClone->tz)->locale($locale ?? $carbonClone->locale)
        ;
    }

    public function getCurrentYear(): string|int
    {
        $carbon = $this->carbonFactory->make(
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
