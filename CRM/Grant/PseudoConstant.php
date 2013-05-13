<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class holds all the Pseudo constants that are specific to Grant. This avoids
 * polluting the core class and isolates the Grant
 */
class CRM_Grant_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Grant Status
   *
   * @var array
   * @static
   */
  private static $grantStatus;

  /**
   * grant Type
   *
   * @var array
   * @static
   */
  private static $grantType;

  /**
   * Get all the n grant statuses
   *
   * @access public
   *
   * @return array - array reference of all grant statuses if any
   * @static
   */
  public static function &grantStatus($id = NULL) {
    if (!self::$grantStatus) {
      self::$grantStatus = array();
      self::$grantStatus = CRM_Core_OptionGroup::values('grant_status');
    }

    if ($id) {
      return self::$grantStatus[$id];
    }

    return self::$grantStatus;
  }

  /**
   * Get all the n grant types
   *
   * @access public
   *
   * @return array - array reference of all grant types if any
   * @static
   */
  public static function &grantType($id = NULL) {
    if (!self::$grantType) {
      self::$grantType = array();
      self::$grantType = CRM_Core_OptionGroup::values('grant_type');
    }

    If ($id) {
      return self::$grantType[$id];
    }

    return self::$grantType;
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * nex time it's requested.
   *
   * @access public
   * @static
   *
   * @param boolean $name pseudoconstant to be flushed
   *
   */
  public static function flush($name) {
   if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }
}

