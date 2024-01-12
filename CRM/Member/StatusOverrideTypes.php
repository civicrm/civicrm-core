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
 * Membership status override types.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    return $overrideType != self::NO;
  }

  public static function isNo($overrideType) {
    return $overrideType == self::NO;
  }

  public static function isPermanent($overrideType) {
    return $overrideType == self::PERMANENT;
  }

  public static function isUntilDate($overrideType) {
    return $overrideType == self::UNTIL_DATE;
  }

}
