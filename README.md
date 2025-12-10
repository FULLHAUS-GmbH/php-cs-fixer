# FULLHAUS PHP-CS-Fixer Config

This repository contains the PHP-CS-Fixer configuration used for projects maintained by [**FULL**HAUS](https://www.fullhaus.de/).

## Installation

Follow these steps to integrate the FULLHAUS PHP-CS-Fixer into your project:

1. Add the repository to the `repositories` field in your `composer.json`:
    ```json
    {
        "repositories": {
            "fullhaus/php-cs-fixer": {
                "type": "vcs",
                "url": "git@github.com:FULLHAUS-GmbH/php-cs-fixer.git"
            }
        }
    }
    ```

2. Require the package using composer:
    ```shell
    composer require fullhaus/php-cs-fixer:dev-main --dev
    ```

3. Create or update your `.php-cs-fixer.php`
    ```php
   <?php

    declare(strict_types=1);

    $config = FULLHAUS\CodingStandards\CsFixerConfig::create();
    $config->getFinder()->in(__DIR__ . '/src');

    return $config;
    ```

   > **NOTE:** For TYPO3 projects, adjust the directory to `$config->getFinder()->in(__DIR__ . '/packages');`.

4. Add the following snippet to your root `composer.json`:
   ```json
    {   
       "scripts": {
          "fix:php": [
             "@fix:php:cs"
          ],
          "fix:php:cs": "php-cs-fixer fix --config .php-cs-fixer.php"
       }
    }
   ```

   > ATTENTION: Beware of copy🍝! There might be an existing `scripts` section in your composer.json already. In this case copy the cs-fixer scripts **only**, if not in there, yet. Anyways, IDE will complain about duplicate associative keys.

## Features

### Header Comment Fixer for Mono Repos

This package includes a custom fixer for managing header comments in mono repo projects. Each package or project can have its own header comment that will be automatically added to PHP files.

**Quick example:**
```php
<?php

declare(strict_types=1);

$config = FULLHAUS\CodingStandards\CsFixerConfig::create();

// Enable header comment
$config->enableHeaderComment(`
▄▄   ▄▄  ▄▄▄▄▄      ▄▄▄  ▄▄▄▄   ▄▄▄▄▄
██▄█▄██  ██▄▄      ██ ██ ██ ██  ██▄▄ 
██▀ ▀██  ██▄▄▄     ██▀██ ██▀█▄  ██▄▄▄+

██▀▀▀▀  ██  ██  ██      ██      █   █  ▄▀▀▀▄  █   █  ▄▀▀▀▄
██▄▄▄   ██  ██  ██      ██      █▄▄▄█  █▄▄▄█  █   █  ▀▄▄▄ 
██      ██  ██  ██      ██      █   █  █   █  █   █  ▄   █
▀▀       ▀▀▀▀   ▀▀▀▀▀▀  ▀▀▀▀▀▀  ▀   ▀  ▀   ▀   ▀▀▀    ▀▀▀ 
`);

$config->getFinder()->in(__DIR__ . '/src');

return $config;
```

**Key features:**
- ✅ **🆕 Automatic package detection from composer.json**
- ✅ **🆕 One configuration for entire monorepo**
- ✅ Configure different headers per package in a mono repo
- ✅ Enable/disable per project
- ✅ Control header placement and spacing
- ✅ Automatic header management (add/update/remove)

**Monorepo example with automatic detection:**
```php
$config->setHeaderComment([
    'enabled' => true,
    'packages_path' => [__DIR__ . '/packages'],
    'header_template' => 'This file is part of {package_name}.',
]);
// Each package gets its own header based on composer.json name!
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
