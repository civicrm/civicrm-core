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
 * This file contains various support functions for test entities in CiviCRM.
 * Historically there is a lot of inconsistency as to how test entities are displayed.
 * This class helps resolve that.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
