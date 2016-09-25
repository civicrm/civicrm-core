<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Utilities for manipulating/inspecting CRM_*_PseudoConstant classes.
 */
class CRM_Utils_PseudoConstant {
  /**
   * CiviCRM pseudoconstant classes for wrapper functions.
   */
  private static $constantClasses = array(
    'CRM_Core_PseudoConstant',
    'CRM_Event_PseudoConstant',
    'CRM_Contribute_PseudoConstant',
    'CRM_Member_PseudoConstant',
  );

  /**
   * @var array
   *   ($name => $className)
   */
  private static $constants = NULL;

  /**
   * Get constant.
   *
   * Wrapper for Pseudoconstant methods. We use this so the calling function
   * doesn't need to know which class the Pseudoconstant is on
   * (some are on the Contribute_Pseudoconstant Class etc
   *
   *
   * @param string $constant
   *
   * @return array
   *   array reference of all relevant constant
   */
  public static function getConstant($constant) {
    $class = self::findConstantClass($constant);
    if ($class) {
      return $class::$constant();
    }
  }

  /**
   * Flush constant.
   *
   * Wrapper for Pseudoconstant methods. We use this so the calling function
   * doesn't need to know which class the Pseudoconstant is on
   * (some are on the Contribute_Pseudoconsant Class etc
   *
   *
   * @param $constant
   *
   * @return array
   *   array reference of all relevant constant
   */
  public static function flushConstant($constant) {
    $class = self::findConstantClass($constant);
    if ($class) {
      $class::flush(lcfirst($constant));
      //@todo the rule is api functions should only be called from within the api - we
      // should move this function to a Core class
      $name = _civicrm_api_get_entity_name_from_camel($constant);
      CRM_Core_OptionGroup::flush($name);
      return TRUE;
    }
  }

  /**
   * Determine where a constant lives.
   *
   * If there's a full, preloaded map, use it. Otherwise, use search
   * class space.
   *
   * @param string $constant
   *   Constant-name.
   *
   * @return string|NULL
   *   class-name
   */
  public static function findConstantClass($constant) {
    if (self::$constants !== NULL && isset(self::$constants[$constant])) {
      return self::$constants[$constant];
    }
    foreach (self::$constantClasses as $class) {
      if (method_exists($class, lcfirst($constant))) {
        return $class;
      }
    }
    return NULL;
  }

  /**
   * Scan for a list of pseudo-constants. A pseudo-constant is recognized by listing
   * any static properties which have corresponding static methods.
   *
   * This may be inefficient and should generally be avoided.
   *
   * @return array
   *   Array of string, constant names
   */
  public static function findConstants() {
    if (self::$constants === NULL) {
      self::$constants = array();
      foreach (self::$constantClasses as $class) {
        foreach (self::findConstantsByClass($class) as $constant) {
          self::$constants[$constant] = $class;
        }
      }
    }
    return array_keys(self::$constants);
  }

  /**
   * Scan for a list of pseudo-constants. A pseudo-constant is recognized by listing
   * any static properties which have corresponding static methods.
   *
   * This may be inefficient and should generally be avoided.
   *
   * @param $class
   *
   * @return array
   *   Array of string, constant names
   */
  public static function findConstantsByClass($class) {
    $clazz = new ReflectionClass($class);
    $classConstants = array_intersect(
      CRM_Utils_Array::collect('name', $clazz->getProperties(ReflectionProperty::IS_STATIC)),
      CRM_Utils_Array::collect('name', $clazz->getMethods(ReflectionMethod::IS_STATIC))
    );
    return $classConstants;
  }

  /**
   * Flush all caches related to pseudo-constants.
   *
   * This may be inefficient and should generally be avoided.
   */
  public static function flushAll() {
    foreach (self::findConstants() as $constant) {
      self::flushConstant($constant);
    }
    CRM_Core_PseudoConstant::flush();
  }

}
