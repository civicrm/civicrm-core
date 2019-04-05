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
 * Membership status override types.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 *
 */
class CRM_Member_StatusOverrideTypes {
  /**
   * The membership status is not overridden
   * and its is subject to membership status rules.
   */
  const NO = 0;

  /**
   * The membership will stay at the selected status
   * and its status is NOT subject to membership
   * status rules.
   */
  const PERMANENT = 1;

  /**
   * The membership status will stay at the
   * selected status and it is NOT subject to membership status rules.
   * However, on the selected date(status_override_end_date),
   * the status override type will automatically change to "NO" thus then
   * the membership becomes subject to membership status rules.
   */
  const UNTIL_DATE = 2;

  /**
   * Gets the list of override types
   * as a list of options to be used
   * for select input.
   *
   * @return array
   *   In ['Type 1 Value' => 'Type 1 Label'] format
   */
  public static function getSelectOptions() {
    return [
      self::NO => ts('No'),
      self::PERMANENT => ts('Override Permanently'),
      self::UNTIL_DATE => ts('Override Until Selected Date'),
    ];
  }

  /**
   * Determines if the override type means
   * that the membership is overridden or not.
   * For now, only "NO" type means that the membership
   * status is not overridden.
   *
   * @param $overrideType
   *
   * @return bool
   */
  public static function isOverridden($overrideType) {
    if ($overrideType == self::NO) {
      return FALSE;
    }

    return TRUE;
  }

  public static function isNo($overrideType) {
    if ($overrideType != self::NO) {
      return FALSE;
    }

    return TRUE;
  }

  public static function isPermanent($overrideType) {
    if ($overrideType != self::PERMANENT) {
      return FALSE;
    }

    return TRUE;
  }

  public static function isUntilDate($overrideType) {
    if ($overrideType != self::UNTIL_DATE) {
      return FALSE;
    }

    return TRUE;
  }

}
