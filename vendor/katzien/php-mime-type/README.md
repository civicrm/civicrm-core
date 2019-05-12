# PhpMimeType
A PHP library to detect the mime type of a file.

[![Build Status](https://travis-ci.org/katzien/PhpMimeType.svg?branch=master)](https://travis-ci.org/katzien/PhpMimeType)
[![Coverage Status](https://coveralls.io/repos/katzien/PhpMimeType/badge.svg)](https://coveralls.io/r/katzien/PhpMimeType)

[![Latest Stable Version](https://poser.pugx.org/katzien/php-mime-type/v/stable.svg)](https://packagist.org/packages/katzien/php-mime-type)
[![Total Downloads](https://poser.pugx.org/katzien/php-mime-type/downloads.svg)](https://packagist.org/packages/katzien/php-mime-type)

[![License](https://poser.pugx.org/katzien/php-mime-type/license.svg)](https://packagist.org/packages/katzien/php-mime-type)

## Not invented here

This is a modernised version of [Jason Sheets's mimetype class](http://www.phpclasses.org/browse/file/2743.html).

## Installation

To add the PhpMimeType library to your project run

```sh
composer require katzien/php-mime-type
```

from the directory where your composer.json file is.

See [Packagist](https://packagist.org/packages/katzien/php-mime-type) for more details.

## Version Guidance

| Version | Status               | PHP version required |
|---------|----------------------|----------------------|
| 1.x     | Maintained           | min. 5.3             |
| 2.x     | Latest (recommended) | min. 5.6             |

If you're using PHP 5.6 or higher, you should use the latest 2.x version. There is no difference in usage between 1.x and 2.x, so the upgrade should not require any code changes.

Differences between 1.x and 2.x:
- syntax
- versions of composer dependencies

## Usage

```php
$type = \MimeType\MimeType::getType('my-file.pdf'); // returns "application/pdf"
```
