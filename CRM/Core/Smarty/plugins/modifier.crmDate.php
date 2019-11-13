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
 * $Id$
 *
 */

/**
 * Convert the date string "YYYY-MM-DD" to "MM<long> DD, YYYY".
 *
 * @param string $dateString
 *   Date which needs to converted to human readable format.
 *
 * @param null $dateFormat
 * @param bool $onlyTime
 *
 * @return string
 *   human readable date format | invalid date message
 */
function smarty_modifier_crmDate($dateString, $dateFormat = NULL, $onlyTime = FALSE) {
  if ($dateString) {
    // this check needs to be type sensitive
    // CRM-3689, CRM-2441
    if ($dateFormat === 0) {
      $dateFormat = NULL;
    }
    if ($onlyTime) {
      $config = CRM_Core_Config::singleton();
      $dateFormat = $config->dateformatTime;
    }

    return CRM_Utils_Date::customFormat($dateString, $dateFormat);
  }
  return '';
}
