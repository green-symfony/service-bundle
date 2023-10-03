<?php

namespace GS\Service\Service;

use function Symfony\Component\String\{
    u,
    b
};

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
	File\File,
	Session\Session
};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use GS\Service\Service\{
    BoolService,
    CarbonService
};

class StringService
{
    public const DOP_WIDTH_FOR_STR_PAD = 10;

    public const EMOJI_START_RANGE      = 0x1F400;
    public const EMOJI_END_RANGE        = 0x1F440;

    public function __construct(
        protected readonly ArrayService $arrayService,
        protected readonly CarbonService $carbonService,
        protected readonly BoolService $boolService,
        protected readonly array $appPassportBodies,
        protected readonly array $configMonths,
        protected readonly string $yearRegex,
        protected readonly string $yearRegexFull,
        protected readonly string $ppiConfigBurFilePrefixRegex,
        protected readonly string $ppiConfigBurFileCountFiguresRegex,
        protected readonly string $ppiConfigBurExtRegex,
        protected readonly string $ppiConfigBurCorrectFilePrefix,
        protected readonly string $ppiConfigBurCorrectExt,
        protected readonly string $ipV4Regex,
        protected readonly string $slashOfIpRegex,
    ) {
    }

    //###> API ###

    public function strContains(
        string $haystack,
        string $needle,
    ): bool {
        $haystack       = Path::normalize(\mb_strtolower(\str_replace(' ', '', $haystack)));
        $needle         = Path::normalize(\mb_strtolower(\str_replace(' ', '', $needle)));

        //\dd($haystack, $needle);
        return (\str_contains($haystack, $needle) || \str_contains($needle, $haystack));
    }

    public function finderPathButNotNames(
        Finder $finder,
        array $names,
    ) {
        if (!empty($names)) {
            $names = \array_map(fn($partOfPath) => '~.*' . $this->getEscapedSpecialCharacters($partOfPath) . '.*~', $names);
            $finder
                ->path($names)
                ->notName($names)
            ;
        }
    }

    /**
        For \str_pad consider only two-byte text

        \str_pad($lines[0], $this->stringService->getOptimalWidthForStrPad($lines[0], $lines)) . ''
    */
    public function getOptimalWidthForStrPad($inputString, array $all): int
    {
        // const part
        $maxLen         = $this->arrayService->getMaxLen($all);
        $const          = $maxLen + self::DOP_WIDTH_FOR_STR_PAD;
        // dynamic part
        $getCountLettersWithousRussanOnes = static fn($string) => \strlen(\preg_replace('~[а-я]~ui', '', (string) $string));
        $currentLen     = \mb_strlen((string) $inputString) - $getCountLettersWithousRussanOnes($inputString);

        // for \str_pad
        return $currentLen + $const;
    }

    public function getPath(
        string ...$parts,
    ): string {
        $NDS                = Path::normalize(\DIRECTORY_SEPARATOR);

        \array_walk($parts, static fn(&$path) => $path = \rtrim(\trim($path), "/\\"));

        $resultPath      = Path::normalize(\implode($NDS, $parts));

        return $resultPath;
    }

    public function getPathnameWithoutExt(string $path): string
    {
        return \preg_replace('~[.][a-zа-я]+$~iu', '', $path);
    }

    public function replaceSlashWithSystemDirectorySeparator(
        string|array $path,
    ): string|array {
        $output = null;

        if (\is_array($path)) {
            \array_walk($path, fn(&$el) => $el = $this->stringreplaceSlashWithSystemDirectorySeparator($el));
            $output = $path;
        } elseif (\is_string($path)) {
            $output = $this->stringreplaceSlashWithSystemDirectorySeparator($path);
        }

        return $output;
    }

    public function getEscapedStrings(array $array): array
    {
        return \array_map(
            fn($partOfPath)
                => '~.*' . $this->getEscapedSpecialCharacters($partOfPath) . '.*~',
            $array,
        );
    }

    public function getEscapedSpecialCharacters(string $string): string
    {
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

    public function getYearBySubstr(
        int|string $yearSubstr,
        bool $fullYear = false,
        bool $throwIfNull = false,
    ): ?string {
        $year           = null;
        $matches        = [];

        $yearSubstr     = (string) $yearSubstr;

        $currentYear                        = $this->carbonService->getCurrentYear();
        $firstTwoFiguresFromCurrentYear     = \substr($currentYear, 0, 2);

        $yearRegex = $this->yearRegex;

        if ($fullYear) {
            $yearRegex = $this->yearRegexFull;
        }

        \preg_match(
            '~(?<year>' . $yearRegex . ')~',
            $yearSubstr,
            $matches,
        );

        if (isset($matches['year']) && $matches['year'] != false) {
            $year = $matches['year'];
        }

        if (\strlen($year) == 2) {
            $year = $firstTwoFiguresFromCurrentYear . $year;
        }

        if ($year === null && $throwIfNull) {
            throw new \Exception('Год не распознан по строке: ' . $yearSubstr);
        }

        return $year;
    }

    public function getMoreSimilarOnCurrentYearBySubstr(
        int|string $yearSubstr,
    ): ?string {
        $year           = null;
        $matches        = [];

        $yearSubstr     = (string) $yearSubstr;

        $currentYear                        = $this->carbonService->getCurrentYear();
        $firstTwoFiguresFromCurrentYear = \substr($currentYear, 0, 2);

        \preg_match_all(
            '~(?<year>' . $this->yearRegex . ')~',
            $yearSubstr,
            $matches,
        );

        if (isset($matches['year'])) {
            $years = $matches['year'];

            foreach ($years as $_year) {
                if (\strlen($_year) == 2) {
                    $_year = (int) ($firstTwoFiguresFromCurrentYear . $_year);
                }
                if ($year === null || $this->getNumberThatMoreSimilarCurrentYear($year, $_year)) {
                    $year = $_year;
                }
            }
        }

        return $year;
    }

    public function removeSubstr(
        string $string,
        string $substr,
    ): string {
        if (!\str_contains($string, $substr)) {
            return $string;
        }
        return \preg_replace('~' . $substr . '~', '', $string);
    }

    public function getFilenameWithExt(
        string $pathname,
        ?string $ext,
    ): string {
		if (\is_null($ext)) return $pathname;
		
        return ''
            . $this->getPathnameWithoutExt(\basename($pathname))
            . ((string) u(\mb_strtolower($ext))->ensureStart('.'))
        ;
    }

    public function getEnsuredRootDrive(
        string $rootDrive
    ): string {
        $rootDrive = \trim(\rtrim($rootDrive, '/\\'));

        $isRoot = static fn($path) => \preg_match('~^[a-zа-я]$~iu', $path) === 1;

        if (!$isRoot($rootDrive)) {
            return $rootDrive;
        }

        return (string) u($rootDrive)->ensureEnd(':/');
    }

    /* WARNING: use this instad of Path::makeAbsolute()
		
		dir1/dir2 + //ipV4 => (save // in the beginning)//ip4/dir1/dir2
		dir1/dir2 + C:/ => C:/dir1/dir2
	*/
    public function makeAbsolute(
        string $path,
        string $basePath,
    ): string {
        $absPath = Path::makeAbsolute($path, $basePath);

        //###> CONSIDER NETWORK PATHS
        if ($this->isNetworkPath($absPath)) {
            $absPath = (string) u(
                \ltrim($absPath, '/\\ \n\r\t\v\x00'),
            )->ensureStart('//');
        }

        return Path::normalize($absPath);
    }

    /* WARNING: use this instad of Path::getDirectory()
		
		//ipV4 => //ipV4
		//ipV4/dir1/dir2 => //ipV4/dir1
		C:/ => C:/
		C:/dir1/dir2 => C:/dir1
	*/
    public function getDirectory(
        string $path,
    ): string {
        $isOnlyNetworkPath = $this->isOnlyNetworkPath(
            $path,
        );

        if ($isOnlyNetworkPath) {
            return Path::normalize($path);
        }

        return Path::normalize(\dirname($path));
    }

    /* WARNING: use this instad of Path::getRoot()
		
		//ipV4/ => //ipV4 (instead of just /)
		//ipV4/dir1/dir2 => //ipV4
		C: => C:/
		C:/dir1/dir2 => C:/
	*/
    public function getRoot(
        string $path,
    ): string {
        $isNetworkPath = $this->isNetworkPath(
            $path,
        );

        if ($isNetworkPath) {
            $ipRoot = null;
            $ipRootName = 'ipRoot';

            $matches = [];
            \preg_match(
                '~^(?<' . $ipRootName . '>' . $this->slashOfIpRegex . '' . $this->ipV4Regex . ').*~',
                \trim($path),
                $matches,
            );
            if ($v = $this->boolService->isGet($matches, $ipRootName)) {
                $ipRoot = $v;
            }

            if ($ipRoot === null) {
                throw new \Exception($ipRootName . ' не был найден из ' . $path);
            }
            return Path::normalize($ipRoot);
        }
        return Path::normalize(Path::getRoot($path));
    }
	
	public function getEmoji(): string {
		[$max, $min] = [
			self::EMOJI_START_RANGE,
			self::EMOJI_END_RANGE,
		];
		if ($min > $max) [$max, $min] = [$min, $max];
		return \IntlChar::chr(\random_int($min, $max));
	}
	
	/*
		always prefers EXISTING FILES
		if $amongExtensions !== null PREFER IT instad of $path possible ext
	*/
	public function getExtFromPath(
		string $path,
		bool $withDotAtTheBeginning = true,
		?array $amongExtensions = null,
	): ?string {
		$ext = null;
		
		//###> $substrExt
		$substrExt = \preg_replace('~^.*([.].+)$~', '$1', $path);
		if ($substrExt == $path) $substrExt = null;
		//###<
		
		//###> $amongExt
		$amongExt = null;
		$amongExtensions ??= [];
		$file = $path;
		if (!empty($amongExtensions) && $substrExt !== null) {
			\array_unshift($amongExtensions, $substrExt);
		}
		foreach($amongExtensions as $cycleEmongExt) {
			$cycleEmongExt = (string) $cycleEmongExt;
			$file = $this->makeAbsolute(
				(string) u($this->getFilenameWithExt($file, $cycleEmongExt)),
				$this->getDirectory($file),
			);
			
			if (\is_file($file)) {
				$amongExt = $cycleEmongExt;
				unset($cycleEmongExt);
				break;
			}
		}
		//###<
		
		//###> PREFERENCES /* != */
		if ($substrExt != null) $ext = $substrExt;
		if ($amongExt != null) $ext = $amongExt;
		//###< PREFERENCES (MORE IMPORTANT)
		
		
		//###> DOT
		if ($ext !== null) {
			if ($withDotAtTheBeginning) {
				if (!\is_null($ext)) $ext = (string) u($ext)->ensureStart('.');
			} else {
				$ext = \ltrim($ext, '.');
			}			
		}
		//###< DOT
		
		return $ext;
	}
	
	/*
		returns null when $string doesn't contain the pattern
	*/
    public function getFromCallbackIfStringLikeRegex(
		string $string,
		array|string $regexs,
		callable|\Closure $callback,
	): mixed {
		if (\is_string($regexs)) $regexs = [$regexs];
		
		foreach($regexs as $regex) {
			if (\preg_match($regex, $string) === 1) {
				return $callback(
					$regex,
				);
			}
		}
		return null;
	}

    //###< API ###
	

    //###> HELPER ###

    private function isOnlyNetworkPath(
        string $path,
    ): bool {
        return \preg_match('~^' . $this->slashOfIpRegex . $this->ipV4Regex . '$~', \trim($path)) === 1;
    }

    private function isNetworkPath(
        string $path,
    ): bool {
        return \preg_match('~^' . $this->slashOfIpRegex . '.*$~', \trim($path)) === 1;
    }

    private function getNumberThatMoreSimilarCurrentYear(
        $firstYear,
        $secontYear,
    ) {
        $currentYear    = (int) $this->carbonService->getCurrentYear();
        $firstYear      = \abs((int) $firstYear);
        $secontYear     = \abs((int) $secontYear);

        if (\abs($currentYear - $firstYear) < \abs($currentYear - $secontYear)) {
            return $firstYear;
        }

        return $secontYear;
    }

    private function stringreplaceSlashWithSystemDirectorySeparator(string $path): string
    {
        return \str_replace(Path::normalize(\DIRECTORY_SEPARATOR), \DIRECTORY_SEPARATOR, $path);
    }

    //###< HELPER ###
}
