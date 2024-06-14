green-symfony/service-bundle
========

# Description


This bundle provides ready to use services:
| Service id |
| ------------- |
| [GS\Service\Service\ArrayService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/ArrayService.php) |
| [GS\Service\Service\BoolService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/BoolService.php) |
| [GS\Service\Service\BufferService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/BufferService.php) |
| [GS\Service\Service\CarbonService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/CarbonService.php) |
| [GS\Service\Service\ClipService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/ClipService.php) |
| [GS\Service\Service\DumpInfoService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/DumpInfoService.php) |
| [GS\Service\Service\FilesystemService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/FilesystemService.php) |
| [GS\Service\Service\HtmlService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/HtmlService.php) |
| [GS\Service\Service\OSService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/OSService.php) |
| [GS\Service\Service\ParserService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/ParserService.php) |
| [GS\Service\Service\RandomPasswordService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/RandomPasswordService.php) |
| [GS\Service\Service\RegexService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/RegexService.php) |
| [GS\Service\Service\StringService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/StringService.php) |

This bundle provides abstract services:
| Service id |
| ------------- |
| [GS\Service\Service\ConfigService](https://github.com/green-symfony/service-bundle/blob/main/src/Service/ConfigService.php) |

# Installation


### Step 1: Download the bundle

### [Before git clone](https://github.com/green-symfony/docs/blob/main/docs/bundles_green_symfony%20mkdir.md)

```console
git clone "https://github.com/green-symfony/service-bundle.git"
```

### Step 2: Require the bundle

In your `%kernel.project_dir%/composer.json`

```json
"require": {
	"green-symfony/service-bundle": "VERSION"
},
"repositories": [
	{
		"type": "path",
		"url": "./bundles/green-symfony/service-bundle"
	}
]
```

Open your console into your main project directory and execute:

```console
composer require "green-symfony/service-bundle"
```

[Binds](https://github.com/green-symfony/docs/blob/main/docs/borrow-services.yaml-section.md)

### Step 3: Usage

**Symfony Autowiring**

These services are already available for using:

```php
namespace YourNamespace;

use GS\Service\Service\StringService;

class YourClass {
	public function __construct(
		private readonly StringService $stringService,
	) {}

	public function yourMethod() {
		return $this->stringService->SOME_METHOD();
	}
}
```

**php extending + Symfony Autowiring**

```php
//###> YOUR FILE #1 ###

namespace App\Service;

use GS\Service\Service\StringService as GSStringService;

class StringService extend GSStringService {}


//###> YOUR FILE #2 ###

use App\Service\StringService;

class YourClass {
	public function __construct(
		private readonly StringService $stringService,
	) {}

	public function yourMethod() {
		return $this->stringService->SOME_METHOD();
	}
}
```