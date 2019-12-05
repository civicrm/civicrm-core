# PSR-6 and PSR-16 Integration tests
[![Gitter](https://badges.gitter.im/php-cache/cache.svg)](https://gitter.im/php-cache/cache?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)
[![Latest Stable Version](https://poser.pugx.org/cache/integration-tests/v/stable)](https://packagist.org/packages/cache/integration-tests)
[![Total Downloads](https://poser.pugx.org/cache/integration-tests/downloads)](https://packagist.org/packages/cache/integration-tests)
[![Monthly Downloads](https://poser.pugx.org/cache/integration-tests/d/monthly.png)](https://packagist.org/packages/cache/integration-tests)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This repository contains integration tests to make sure your implementation of a PSR-6 and/or PSR-16 cache follows the rules by PHP-FIG.
It is a part of the PHP Cache organisation. To read about us please read the shared documentation at [www.php-cache.com](http://www.php-cache.com).

### Install

```bash
composer require --dev cache/integration-tests:dev-master
```

### Use

Create a test that looks like this:

```php
class PoolIntegrationTest extends CachePoolTest
{
    public function createCachePool()
    {
        return new CachePool();
    }
}
```

You could also test your tag implementation:

```php
class TagIntegrationTest extends TaggableCachePoolTest
{
    public function createCachePool()
    {
        return new CachePool();
    }
}
```

You can also test a PSR-16 implementation:

```php
class CacheIntegrationTest extends SimpleCacheTest
{
    public function createSimpleCache()
    {
        return new SimpleCache();
    }
}
```

### Contribute

Contributions are very welcome! Send a pull request or
report any issues you find on the [issue tracker](http://issues.php-cache.com).
