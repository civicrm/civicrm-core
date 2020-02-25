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
 * This file contains various support functions for test entities in CiviCRM.
 * Historically there is a lot of inconsistency as to how test entities are displayed.
 * This class helps resolve that.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Core_TestEntity.
 */
class CRM_Core_TestEntity {

  // @todo extend this class to include functions that control when/where test entities are displayed
  //  and then use those functions everywhere we can display test transactions.
  // Ideally the display of test transactions would be a per-user setting or permission
  //  so it can be toggled on/off as required and does not affect "day-to-day" usage.

  /**
   * Append "test" text to a string. eg. Member Dues (test) or My registration (test)
   *
   * @param string $text
   *
   * @return string
   */
  public static function appendTestText($text) {
    return $text . ' ' . ts('(test)');
  }

}
