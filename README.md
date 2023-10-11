green-symfony/service-bundle
========

# Description


This bundle provides:
| Service | Description | Code |
| ------------- | ------------- | ------------- |
|  |  |  |

# Installation


### Step 1: Download the bundle

[Before git clone](https://github.com/green-symfony/docs/blob/main/docs/bundles_green_symfony%20mkdir.md)

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

### Step 3: Usage

With the help of the Symfony Autowiring use these services:

```php
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