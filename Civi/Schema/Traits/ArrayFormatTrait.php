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
 *
 *
 * @package Civi\Schema\Traits
 */
trait ArrayFormatTrait {

  /**
   * Gets all public variables, converted to snake_case
   *
   * @return array
   */
  public function toArray() {
    // Anonymous class will only have access to public vars
    $getter = new class {

      function getPublicVars($object) {
        return get_object_vars($object);
      }

    };

    // If getOptions was never called, make options a boolean
    if (property_exists($this, 'options') && !isset($this->options)) {
      $this->options = isset($this->optionsCallback);
    }

    $ret = [];
    foreach ($getter->getPublicVars($this) as $key => $val) {
      $key = strtolower(preg_replace('/(?=[A-Z])/', '_$0', $key));
      $ret[$key] = $val;
    }
    return $ret;
  }

  /**
   * Populate this field-spec using values from an array.
   *
   * @param iterable $values
   *   List of public variables, expressed in snake_case.
   *   Ex: ['title' => 'Color', 'default_value' => '#f00']
   * @param  bool $strict
   *   In strict mode, properties are only accepted if they are formally defined on the current class.
   * @return $this
   */
  public function loadArray(iterable $values, bool $strict = FALSE) {
    foreach ($values as $key => $value) {
      $field = \CRM_Utils_String::convertStringToCamel($key, FALSE);
      $setter = 'set' . ucfirst($field);
      if ($strict && !property_exists($this, $field) && !method_exists($this, $setter) && $value !== NULL) {
        throw new \CRM_Core_Exception(sprintf('Cannot assign field (%s::%s aka %s)', get_class($this), $field, $key));
      }
      if (is_callable([$this, $setter])) {
        $this->{$setter}($value);
      }
      elseif (!$strict || property_exists($this, $field)) {
        $this->{$field} = $value;
      }
    }
    return $this;
  }

}
