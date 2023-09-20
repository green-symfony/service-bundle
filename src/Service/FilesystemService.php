<?php

namespace GS\Service;

use function Symfony\Component\String\u;

use Symfony\Component\Uid\Uuid;
use Carbon\Carbon;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Filesystem\{
    Path,
    Filesystem
};
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
    StringService,
    ConfigsService,
    DumpInfoService,
    ParametersService
};

class FilesystemService
{
    private OptionsResolver $demandsOptionsResolver;
    private readonly array $demandsKeys;
    private readonly Filesystem $filesystem;

    public const MAX_FILENAME_LEN = 150;

    public function __construct(
        private readonly DumpInfoService $dumpInfoService,
        private readonly StringService $stringService,
        private readonly string $partPathFromLocalToPassportBody,
        //###> without types
        private string $localDriveForTest,
        private readonly string $appEnv,
        private $carbonFactory,
        private $slugger,
    ) {
        $this->filesystem           = new Filesystem();

        $demands = [
            'exists',
            'isAbsolutePath',
            'isDir',
            'isFile',
        ];
        $this->demandsKeys              = \array_combine($demands, $demands);

        $this->demandsOptionsResolver   = new OptionsResolver();
        $this->configureOptions();
    }

    //###> API ###

    public function throwIfNot(
        array $demands,
        ?string ...$absPaths,
    ): void {
        $this->ifNot(true, $demands, ...$absPaths);
    }

    public function getErrorsIfNot(
        array $demands,
        ?string ...$absPaths,
    ): array {
        return $this->ifNot(false, $demands, ...$absPaths);
    }

    public function executeWithoutChangeMTime(
        \Closure|\callable $callback,
        string $from,
        string $to,
        bool $override,
        bool $move,
        bool $isMakeIt = false,
    ) {
        /*
        \dd(
            $from,
            $to,
        );
        */
        return $this->make(
            $callback,
            $from,
            $to,
            $override,
            $move,
            isMakeIt:               $isMakeIt,
            withoutChangeMTime:     true,
        );
    }

    public function copyWithoutChangeMTime(
        string $from,
        string $to,
        bool $override,
        bool $move,
        bool $isMakeIt = false,
    ) {
        return $this->make(
            'copy',
            $from,
            $to,
            $override,
            $move,
            isMakeIt:               $isMakeIt,
            withoutChangeMTime:     true,
        );
    }

    public function copyWithChangeMTime(
        string $from,
        string $to,
        bool $override,
        bool $move,
        bool $isMakeIt = false,
    ) {
        return $this->make(
            'copy',
            $from,
            $to,
            $override,
            $move,
            isMakeIt:               $isMakeIt,
            withoutChangeMTime:     false,
        );
    }

    // TODO: it works, but redo
    public function getLocalRoot(): string
    {
        if ($this->appEnv === 'test') {
            return $this->localDriveForTest;
        }

        $NDS = Path::normalize(\DIRECTORY_SEPARATOR);
        return (string) u(\explode($NDS, Path::normalize(__DIR__))[0])->ensureEnd($NDS);
    }

    public function exists(
        string $path,
    ): bool {
        return $this->filesystem->exists($path);
    }

    public function assignCMTime(
        string $sourceCATimeAbsPath,
        string $toAbsPath,
    ): void {
        $this->throwIfNot(
            [
                'exists',
                'isAbsolutePath',
                'isFile',
            ],
            $sourceCATimeAbsPath,
            $toAbsPath,
        );

        $splFileInfoSource      = new \SplFileInfo($sourceCATimeAbsPath);
        $modifiedTimestamp      = $splFileInfoSource->getMTime();

        /*
        $timePattern            = 'd-m-Y H:i:s';
        $createdTime            = $this->carbonFactory->make(Carbon::createFromTimestamp($splFileInfoSource->getCTime()))->format($timePattern);
        $modifiedTime           = $this->carbonFactory->make(Carbon::createFromTimestamp($modifiedTimestamp))->format($timePattern);
        $command                = Path::normalize($this->appPathToCreateDateTimeSetter);

        // modified and created
        $assignCMTimeCommand    = '"'.$command.'"'
            . ' ' . 'setfiletime'
            // path
            . ' ' . '"' . $toAbsPath . '"'
            //created
            . ' ' . '"' . $createdTime . '"'
            //modified
            . ' ' . '"' . $modifiedTime . '"'
        ;
        \system($assignCMTimeCommand);
        */

        // modified
        $this->filesystem->touch($toAbsPath, $modifiedTimestamp);
    }

    public function getSmallestDrive(): string
    {
        $drives         = \explode(' ', ((string) u(\shell_exec('fsutil fsinfo drives'))->collapseWhitespace()));
        $smallestDrive  = null;

        foreach ($drives as $drive) {
            $errors = $this->getErrorsIfNot(
                [
                    'exists',
                ],
                $drive,
            );
            if (empty($errors)) {
                if ($smallestDrive === null) {
                    $smallestDrive     = $drive;
                }
                if (\disk_total_space($drive) < \disk_total_space($smallestDrive)) {
                    $smallestDrive = $drive;
                }
            }
        }

        return Path::normalize($smallestDrive);
    }

    public function mkdir(string|iterable $dirs, int $mode = 0766): void
    {
        $this->filesystem->mkdir($dirs, $mode);
    }

    public function getDesktopPath(): string
    {
        $desktop = Path::normalize(\getenv("HOMEDRIVE") . \getenv("HOMEPATH") . "\Desktop");

        $this->throwIfNot(
            [
                'exists',
                'isAbsolutePath',
                'isDir',
            ],
            $desktop,
        );

        return $desktop;
    }

    public function firstFileNewer(
        \SplFileInfo|string $first,
        \SplFileInfo|string $second,
    ): bool {

        if (\is_string($first)) {
            $first      = Path::normalize($first);

            $errors = $this->getErrorsIfNot(
                [
                    'exists',
                ],
                $first,
            );
            if (!empty($errors)) {
                return false;
            }
        }

        if (\is_string($second)) {
            $second             = Path::normalize($second);

            $errors = $this->getErrorsIfNot(
                [
                    'exists',
                ],
                $second,
            );

            if (!empty($errors)) {
                return true;
            }
        }

        $carbonFirst        = $this->getCarbonByFile($first);
        $carbonSecond       = $this->getCarbonByFile($second);

        return $carbonFirst > $carbonSecond;
    }

    public function tempnam(
        ?string $path = null,
        string $ext = 'txt',
    ): string {
        $fileExists     = empty($this->getErrorsIfNot(
            [
                'exists',
                'isFile',
            ],
            $path,
        ));
        if (!\is_null($path) && $fileExists) {
            $ext = (new \SplFileInfo($path))->getExtension();
        }

        $ext = (string) u($ext)->ensureStart('.');

        return $this->filesystem->tempnam(
            Path::normalize(\sys_get_temp_dir()),
            \substr($this->slugger->slug('PPI' . Uuid::v1()), 0, self::MAX_FILENAME_LEN),
            $ext,
        );
    }

    public function addLine(string $absPath, $content): void
    {
        $exists     = $this->isAbsPathExists($absPath);
        if ($exists) {
            $this->filesystem->appendToFile($absPath, $content, true);
        }
    }

    public function deleteByAbsPathIfExists(
        string $absPath,
    ): void {
        $exists = $this->isAbsPathExists($absPath);
        if (!$exists) {
            return;
        }
        $this->filesystem->remove($absPath);
    }

    public function getLocalRootToPassportsBodies(): string
    {
        return $this->stringService->getPath(
            $this->getLocalRoot(),
            $this->partPathFromLocalToPassportBody,
        );
    }

    //###< API ###

    //###> HELPER ###

    private function getCarbonByFile(
        \SplFileInfo|string $file,
    ): Carbon {
        if (\is_string($file)) {
            $this->throwIfNot(
                [
                    'isFile',
                ],
                $file,
            );
            $carbon     = Carbon::createFromTimestamp((new \SplFileInfo($file))->getMTime());
        } else {
            $carbon     = Carbon::createFromTimestamp($file->getMTime());
        }

        return $carbon;
    }

    private function ifNot(
        bool $throw,
        array $demands,
        ?string ...$absPaths,
    ): array {
        $demands = \array_combine($demands, $demands);

        $this->demandsOptionsResolver->resolve($demands);

        $errors = [];
        foreach ($absPaths as $absPath) {
            if ($absPath === null) {
                $absPath = '';
            }

            if (isset($demands[$this->demandsKeys['exists']])               && !$this->filesystem->exists($absPath)) {
                $errors[$absPath][]     = 'существующим';
            }
            if (isset($demands[$this->demandsKeys['isAbsolutePath']])       && !Path::isAbsolute($absPath)) {
                $errors[$absPath][]     = 'абсолютным';
            }
            if (isset($demands[$this->demandsKeys['isDir']])                && !\is_dir($absPath)) {
                $errors[$absPath][]     = 'папкой';
            }
            if (isset($demands[$this->demandsKeys['isFile']])               && !\is_file($absPath)) {
                $errors[$absPath][]     = 'файлом';
            }
        }

        if ($throw && !empty($errors)) {
            $errMessage = '';
            foreach ($errors as $path => $error) {
                $path = $this->stringService->replaceSlashWithSystemDirectorySeparator($path);
                $errMessage .= 'Переданный "' . $path  . '" должен быть: (' . \implode(', ', $error) . ')' . \PHP_EOL;
            }
            throw new \Exception($errMessage);
            return $errors;
        }

        return $errors;
    }

    private function configureOptions()
    {
        $this->demandsOptionsResolver
            ->setDefaults($this->demandsKeys)
        ;
    }

    private function make(
        string|\Closure|\callable $type,
        string $from,
        string $to,
        bool $override,
        bool $move,
        bool $isMakeIt,
        bool $withoutChangeMTime = true,
    ): array {
        $madeResults = [];

        $defaultPredicatForMakeIt       = $override || $this->firstFileNewer(first: $from, second: $to);
        $exactlyMakeIt                  = ($isMakeIt === true) || $defaultPredicatForMakeIt;

        if ($exactlyMakeIt) {
            $this->throwIfNot(
                [
                    'exists',
                    'isAbsolutePath',
                    'isFile',
                ],
                $from,
            );
            $this->throwIfNot(
                [
                    'isAbsolutePath',
                ],
                $to,
            );

            // from -> tmp

            try {
                $toTmp = $this->tempnam($to);
            } catch (\Exception $e) {
                return $madeResults;
            }

            $this->detectMakeTypeAndExecute(
                $type,
                $from,
                $toTmp,
                $exactlyMakeIt,
            );

            // tmp -> realTo
            // use dirname instead of $this->stringService->getDirectory for host address
            $this->filesystem->mkdir($this->stringService->getDirectory($to), 0766);

            // before rename need to remove $to
            if (\is_file($to)) {
                $this->filesystem->remove($to);
            }
            $this->filesystem->rename(
                $toTmp,
                $to,
                overwrite: $exactlyMakeIt,
            );

            try {
                if ($withoutChangeMTime) {
                    $this->assignCMTime($from, $to);
                }
            } catch (\Exception $e) {
                echo 'ERROR: не получилось установить время файла "' . $to . '"' . \PHP_EOL . \PHP_EOL;
            }

            if (\is_file($toTmp)) {
                $this->filesystem->remove($toTmp);
            }

            try {
                if ($move && \is_file($from)) {
                    $this->filesystem->remove($from);
                }
            } catch (\Exception $e) {
                echo 'ERROR: Не удалось удалить ' . $this->stringService->replaceSlashWithSystemDirectorySeparator($from) . \PHP_EOL . \PHP_EOL;
            }

            $madeResults     = [
                'from'      => $from,
                'to'        => $to,
            ];
        }

        return $madeResults;
    }

    private function isAbsPathExists(string $absPath): bool
    {
        return empty($this->getErrorsIfNot(
            [
                'isAbsolutePath',
                'exists',
            ],
            $absPath,
        ));
    }

    private function detectMakeTypeAndExecute(
        $type,
        $from,
        $to,
        $exactlyMakeIt,
    ) {
        if (\is_string($type)) {
            if ($type == 'copy') {
                $this->filesystem->copy(
                    $from,
                    $to,
                    overwriteNewerFiles:    $exactlyMakeIt,
                );
            }
            return;
        }

        if ($type instanceof \callable || $type instanceof \Closure) {
            $type(
                $from,
                $to,
                $exactlyMakeIt,
            );
            return;
        }
    }

    //###< HELPER ###
}