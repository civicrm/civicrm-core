<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Schema\Traits;

use Civi\Api4\Utils\ReflectionUtils;

/**
 * Automatically define getter/setter methods for public and protected fields.
 *
 * BASIC USAGE
 *
 * - Choose a class
 * - Add the trait (`use MagicGetterSetterTrait;`).
 * - Add a public or protected property  (`protected $fooBar;`).
 * - When using the class, you may now call `setFooBar($value)` and `getFooBar()`.
 *
 * TIPS AND TRICKS
 *
 * - To provide better hints/DX in IDEs, you may add the `@method` notations
 *   to the class docblock. There are several examples of this in APIv4
 *   (see e.g. `AbstractAction.php` or `AbstractQueryAction.php`).
 * - When/if you need to customize the behavior of a getter/setter, then simply
 *   add your own method. This takes precedence over magic mehods.
 * - If a field name begins with `_`, then it will be excluded.
 *
 * @package Civi\Schema\Traits
 */
trait MagicGetterSetterTrait {

  /**
   * @var int
   */
  private static $CACHE_CHECK_INTERVAL = 100;

  /**
   * @var int
   */
  private static $CACHE_MAX_SIZE = 500;

  /**
   * @var int
   */
  private static $CACHE_TRIM_SIZE = 250;

  /**
   * @var float
   */
  private static $MEMORY_THRESHOLD = 0.75;

  /**
   * Magic function to provide getters/setters.
   *
   * @param string $method
   * @param array $arguments
   * @return static|mixed
   * @throws \CRM_Core_Exception
   */
  public function __call($method, $arguments) {
    $mode = substr($method, 0, 3);
    $prop = lcfirst(substr($method, 3));
    $props = static::getMagicProperties();
    if (isset($props[$prop])) {
      switch ($mode) {
        case 'get':
          return $this->$prop;

        case 'set':
          if (count($arguments) < 1) {
            throw new \CRM_Core_Exception(sprintf('Missing required parameter for method %s::%s()', static::class, $method));
          }
          $this->$prop = $arguments[0];
          return $this;
      }
    }

    throw new \CRM_Core_Exception(sprintf('Unknown method: %s::%s()', static::class, $method));
  }

  /**
   * Get a list of class properties for which magic methods are supported.
   *
   * @return array
   *   List of supported properties, keyed by property name.
   *   Array(string $propertyName => bool $true).
   */
  protected static function getMagicProperties(): array {
    // Thread-local cache of class metadata. Class metadata is immutable at runtime, so this is strictly write-once. It should ideally be reused across varied test-functions.
    static $caches = [];
    // Track access order for proper LRU
    static $accessOrder = [];
    static $accessCount = 0;

    $CLASS = static::class;
    // Memory management: Prevent unbounded cache growth that can cause memory exhaustion
    // This addresses issues where bulk operations with many extensions can accumulate
    // thousands of class metadata entries, leading to multi-gigabyte memory allocations
    ++$accessCount;
    if ($accessCount % self::$CACHE_CHECK_INTERVAL === 0) {
      $currentMemory = memory_get_usage(TRUE);

      // Get PHP memory limit in bytes using robust regex parsing
      $memoryLimitBytes = self::parseMemoryLimit();
      // If we're using more than threshold of available memory, clear the cache
      if ($memoryLimitBytes > 0 && $currentMemory > ($memoryLimitBytes * self::$MEMORY_THRESHOLD)) {
        $caches = [];
        $accessOrder = [];
        if (function_exists('gc_collect_cycles')) {
          gc_collect_cycles();
        }
        \Civi::log()->info("MagicGetterSetterTrait: Emergency cache clear at " . round($currentMemory / 1048576, 1) . "MB");
      }
      // Also limit cache size to prevent unbounded growth even with available memory
      elseif (count($caches) > self::$CACHE_MAX_SIZE) {
        // Proper LRU: Remove least recently used entries
        $toRemove = array_slice($accessOrder, 0, count($accessOrder) - self::$CACHE_TRIM_SIZE);
        foreach ($toRemove as $classToRemove) {
          unset($caches[$classToRemove]);
        }
        $accessOrder = array_slice($accessOrder, -self::$CACHE_TRIM_SIZE);

        if (function_exists('gc_collect_cycles')) {
          gc_collect_cycles();
        }
        \Civi::log()->debug("MagicGetterSetterTrait: LRU trimmed cache to " . self::$CACHE_TRIM_SIZE . " classes");
      }
    }

    // Update access order for proper LRU tracking
    // Remove if exists
    $accessOrder = array_diff($accessOrder, [$CLASS]);
    // Add to end (most recent)
    $accessOrder[] = $CLASS;
    $cache =& $caches[$CLASS];
    if ($cache === NULL) {
      $cache = [];
      foreach (ReflectionUtils::findStandardProperties(static::class) as $property) {
        /** @var \ReflectionProperty $property */
        $cache[$property->getName()] = TRUE;
      }
    }
    return $cache;
  }

  /**
   * Parse PHP memory limit string into bytes.
   * Uses native ini_parse_quantity() on PHP 8.2+, falls back to regex parsing.
   * Handles formats like "128M", "1G", "512MB", "2048", "-1"
   *
   * @return int Memory limit in bytes, 0 if unlimited or invalid
   */
  private static function parseMemoryLimit(): int {
    $memoryLimit = ini_get('memory_limit');
    if (!$memoryLimit || $memoryLimit === '-1') {
      // Unlimited
      return 0;
    }
    // Use native function if available (PHP 8.2+)
    if (function_exists('ini_parse_quantity')) {
      $bytes = ini_parse_quantity($memoryLimit);
      return $bytes === -1 ? 0 : (int) $bytes;
    }
    // Fallback for PHP < 8.2: Use regex to parse various formats
    if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMGT]?)B?$/i', trim($memoryLimit), $matches)) {
      $value = (float) $matches[1];
      $unit = strtoupper($matches[2] ?? '');

      switch ($unit) {
        case 'T':
          return (int) ($value * 1024 * 1024 * 1024 * 1024);

        case 'G':
          return (int) ($value * 1024 * 1024 * 1024);

        case 'M':
          return (int) ($value * 1024 * 1024);

        case 'K':
          return (int) ($value * 1024);

        default:
          return (int) $value;
      }
    }

    // Invalid format, treat as unlimited
    return 0;
  }

}
