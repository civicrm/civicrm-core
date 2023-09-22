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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_Constant
 */
class CRM_Utils_Constant {

  /**
   * Determine the value of a constant, if any.
   *
   * If the specified constant is undefined, check for an environment
   * variable, defaulting the passed in default value.
   *
   * @param string $name
   * @param mixed $default
   *   (optional)
   * @return mixed
   */
  public static function value(string $name, $default = NULL) {
    if (defined($name)) {
      return constant($name);
    }
    if (($value = getenv($name)) !== FALSE) {
      define($name, $value);
      return $value;
    }
    return $default;
  }

}
