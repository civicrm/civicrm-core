# restricted-unserialize

[![Build Status](https://travis-ci.org/xKerman/restricted-unserialize.svg?branch=master)](https://travis-ci.org/xKerman/restricted-unserialize)
[![codecov](https://codecov.io/gh/xKerman/restricted-unserialize/branch/master/graph/badge.svg)](https://codecov.io/gh/xKerman/restricted-unserialize)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/xKerman/restricted-unserialize/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/xKerman/restricted-unserialize/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/xkerman/restricted-unserialize/v/stable)](https://packagist.org/packages/xkerman/restricted-unserialize)

This composer package provides `unserialize` function that is safe for [PHP Obejct Injection (POI)](https://www.owasp.org/index.php/PHP_Object_Injection).

If normal `unserialize` function is used for deserializing user input in your PHP application:

1. Don't use this package, use `json_decode` in order to avoid PHP Object Injection
2. If compatibility matters, first use this function and then try to use `json_decode` in the near future


## Why POI-safe?

`unserialize` function in this package only deserializes boolean, integer, floating point number, string, and array, and not deserializes object instance.
Since any instances that has magic method for POP chain (such as `__destruct` or `__toString`) cannot instantiate, any plan to exploit POP chain just fails.
( You can read detailed explanation of POP chain https://www.insomniasec.com/downloads/publications/Practical%20PHP%20Object%20Injection.pdf )



## Installation

```
$ composer require xkerman/restricted-unserialize
```


## How to use

if your PHP version > 5.5:

```
require 'path/to/vendor/autoload.php';

use function xKerman\Restricted\unserialize;
use xKerman\Restricted\UnserializeFailedException;

try {
    var_dump(unserialize($data));
} catch (UnserializeFailedException $e) {
    echo 'failed to unserialize';
}
```

if your PHP version >= 5.3 and <= 5.5:

```
require 'path/to/vendor/autoload.php';

use xKerman\Restricted;
use xKerman\Restricted\UnserializeFailedException;

try {
    var_dump(Restricted\unserialize($data));
} catch (UnserializeFailedException $e) {
    echo 'failed to unserialize';
}
```

if your PHP version is 5.2:

```
require_once 'path/to/generated/src/xKerman/Restricted/bootstrap.php';

try {
    var_dump(xKerman_Restricted_unserialize($data));
} catch (xKerman_Restricted_UnserializeFailedException $e) {
    echo 'failed to unserialize';
}
```

## Related other packages

### mikegarde/unserialize-fix

[mikegarde/unserialize-fix](https://github.com/MikeGarde/unserialize-fix) package provides `\unserialize\fix` function that tries to use `unserialize` function first.  So the function is not POI-safe.


### academe/serializeparser

[academe/serializeparser](https://github.com/academe/SerializeParser) package privides `\Academe\SerializeParser\Parser::parse` method that is PHP-implemented `unserialize`, but doesn't deserialize object instances.  So the method seems that POI-safe, but there is no test.


### jeroenvdheuve/serialization

[jeroenvdheuve/serialization](https://github.com/jeroenvdheuvel/serialization) package provides `\jvdh\Serialization\Unserializer\unserialize` method that is also PHP-implemented `unserialize`, and doesn't deserialize object instance.  So the method seems that POI-safe.
The method can deserialize serialized PHP references, which cannot deserialized by this (xkerman/restricted-unserilize) package.  By using PHP reference, we can create cyclic structure, but that makes migration to `json_decode` harder, since JSON doesn't support cyclic structure decode/encode.


## Development

To generate code for PHP 5.2, run `composer run generate`.
Generated code will be saved under `genereated/` directory.


## LICENSE

MIT License
