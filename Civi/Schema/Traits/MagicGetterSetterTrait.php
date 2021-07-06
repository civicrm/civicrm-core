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
    // Thread-local cache of class metadata. This is strictly readonly and immutable, and it should ideally be reused across varied test-functions.
    static $cache = [];

    if (!isset($cache[static::CLASS])) {
      try {
        $clazz = new \ReflectionClass(static::CLASS);
      }
      catch (\ReflectionException $e) {
        // This shouldn't happen. Cast to RuntimeException so that we don't have a million `@throws` statements.
        throw new \RuntimeException(sprintf("Class %s cannot reflect upon itself.", static::CLASS));
      }

      $fields = [];
      foreach ($clazz->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC) as $property) {
        $name = $property->getName();
        if (!$property->isStatic() && $name[0] !== '_') {
          $fields[$name] = TRUE;
        }
      }
      unset($clazz);
      $cache[static::CLASS] = $fields;
    }
    return $cache[static::CLASS];
  }

}
