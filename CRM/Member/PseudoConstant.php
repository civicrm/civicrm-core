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
 * This class holds all the Pseudo constants that are specific to the civimember component. This avoids
 * polluting the core class and isolates the mass mailer class
 */
class CRM_Member_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Membership types.
   * @var array
   */
  private static $membershipType;

  /**
   * Membership types.
   * @var array
   */
  private static $membershipStatus;

  /**
   * Get all the membership types.
   *
   *
   * @param int $id
   * @param bool $force
   *
   * @return array
   *   array reference of all membership types if any
   */
  public static function membershipType($id = NULL, $force = TRUE) {
    if (!self::$membershipType || $force) {
      CRM_Core_PseudoConstant::populate(self::$membershipType,
        'CRM_Member_DAO_MembershipType',
        FALSE, 'name', 'is_active', NULL, 'weight', 'id'
      );
    }
    if ($id) {
      if (array_key_exists($id, self::$membershipType)) {
        return self::$membershipType[$id];
      }
      else {
        $result = NULL;
        return $result;
      }
    }
    return self::$membershipType;
  }

  /**
   * Get all the membership statuss.
   *
   *
   * @param int $id
   * @param null $cond
   * @param string $column
   * @param bool $force
   *
   * @param bool $allStatus
   *
   * @return array
   *   array reference of all membership statuses if any
   */
  public static function &membershipStatus($id = NULL, $cond = NULL, $column = 'name', $force = FALSE, $allStatus = FALSE) {
    if (self::$membershipStatus === NULL) {
      self::$membershipStatus = [];
    }

    $cacheKey = $column;
    if ($cond) {
      $cacheKey .= "_{$cond}";
    }
    if (!isset(self::$membershipStatus[$cacheKey]) || $force) {
      CRM_Core_PseudoConstant::populate(self::$membershipStatus[$cacheKey],
        'CRM_Member_DAO_MembershipStatus',
        $allStatus, $column, 'is_active', $cond, 'weight'
      );
    }

    $value = NULL;
    if ($id) {
      $value = self::$membershipStatus[$cacheKey][$id] ?? NULL;
    }
    else {
      $value = self::$membershipStatus[$cacheKey];
    }

    return $value;
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * next time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
    // The preferred source of membership pseudoconstants is in fact the Core class.
    // which buildOptions accesses - better flush that too.
    CRM_Core_PseudoConstant::flush();
  }

}
