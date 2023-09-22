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
            throw new \CRM_Core_Exception(sprintf('Missing required parameter for method %s::%s()', static::CLASS, $method));
          }
          $this->$prop = $arguments[0];
          return $this;
      }
    }

    throw new \CRM_Core_Exception(sprintf('Unknown method: %s::%s()', static::CLASS, $method));
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
    $CLASS = static::CLASS;
    $cache =& $caches[$CLASS];
    if ($cache === NULL) {
      $cache = [];
      foreach (ReflectionUtils::findStandardProperties(static::CLASS) as $property) {
        /** @var \ReflectionProperty $property */
        $cache[$property->getName()] = TRUE;
      }
    }
    return $cache;
  }

}
