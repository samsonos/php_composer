#[SamsonPHP](http://samsonphp.com/) - composer packages list generator

This module create sorted by priority list of project composer packages.

[![Latest Stable Version](https://poser.pugx.org/samsonos/php_composer/v/stable.svg)](https://packagist.org/packages/samsonos/php_composer)
[![Build Status](https://travis-ci.org/samsonos/php_composer.png)](https://travis-ci.org/samsonos/php_composer)
[![Coverage Status](https://img.shields.io/coveralls/samsonos/php_composer.svg)](https://coveralls.io/r/samsonos/php_composer?branch=master)
[![Code Climate](https://codeclimate.com/github/samsonos/php_composer/badges/gpa.svg)](https://codeclimate.com/github/samsonos/php_composer)
[![Total Downloads](https://poser.pugx.org/samsonos/php_composer/downloads.svg)](https://packagist.org/packages/samsonos/php_composer)

## Usage

To work with this module you should get composer instance:
```php
$composer = new \samson\composer\Composer($systemPath, $lockFileName);
```
  * ```$systemPath``` - Path to current web-application
  * ```$lockFileName``` - Composer lock file name (by default is set to ```'composer.lock'```)
    
To configure module there are methods:
  * ```addVendor($vendor)``` - Add available vendor (```$vendor``` is the available vendor)
  * ```setIgnoreKey($ignoreKey)``` - Set name of composer extra parameter to ignore package (```$ignoreKey``` is name). Composer usage example:```"extra": { "samson_module_ignore": "1" }``` (```$composer->setIgnoreKey('samson_module_ignore')```)  
  * ```setIncludeKey($includeKey)``` - Set name of composer extra parameter to include package (```$includeKey``` is name). Composer usage example:```"extra": { "samson_module_include": "1" }``` (```$composer->setIncludeKey('samson_module_include')```)
  * ```addIgnorePackage($package)``` - Add ignored package (```$package``` is the ignored package)
    
To create sorted list of project composer packages you can use method ```create()```.
Example usage:
```php
$composer = new \samson\composer\Composer($systemPath);
$composer
    ->addVendor('samsonos')
    ->setIgnoreKey('samson_module_ignore')
    ->addIgnorePackage('samsonos/php_core')
    ->addIgnorePackage('samsonos/php_event');
$composerModules = $composer->create();
```

Developed by [SamsonOS](http://samsonos.com/)