This is a fork of the unit-test from https://github.com/php-cache/integration-tests/ which provides support for older
versions of PHPUnit. It merely:

* Changes the base-class to `PHPUnit_Framework_TestCase`.
* Changes the name to avoid collsions (`Cache\IntegrationTests\LegacySimpleCacheTest`).

This class is only used for testing -- it is not required at runtime.
