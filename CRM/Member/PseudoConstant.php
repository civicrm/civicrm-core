<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
      $value = CRM_Utils_Array::value($id, self::$membershipStatus[$cacheKey]);
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
  }

}
