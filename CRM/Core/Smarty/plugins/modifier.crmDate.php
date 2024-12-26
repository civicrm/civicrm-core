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
 * Convert the date string "YYYY-MM-DD" to "MM<long> DD, YYYY".
 *
 * @param string $dateString
 *   Date which needs to converted to human readable format.
 *
 * @param string|null $dateFormat
 *   A string per https://www.php.net/manual/en/function.strftime.php or
 *   one of our configured formats name - eg
 *    - dateformatDatetime
 *    - dateformatFull
 *    - dateformatPartial
 *    - dateformatTime
 *    - dateformatYear
 *    - dateformatFinancialBatch
 *    - dateformatshortdate
 *
 * @param bool $onlyTime
 *
 * @return string
 *   human readable date format | invalid date message
 */
function smarty_modifier_crmDate($dateString, ?string $dateFormat = NULL, bool $onlyTime = FALSE): string {
  if ($dateFormat === 'Timestamp') {
    return strtotime($dateString);
  }
  if ($dateString) {
    $configuredFormats = [
      'Datetime',
      'Full',
      'Partial',
      'Time',
      'Year',
      'FinancialBatch',
      'shortdate',
    ];
    if (in_array($dateFormat, $configuredFormats, TRUE)) {
      $dateFormat = Civi::settings()->get('dateformat' . $dateFormat);
    }
    // this check needs to be type sensitive
    // CRM-3689, CRM-2441
    if ($dateFormat === 0) {
      $dateFormat = NULL;
    }
    if ($onlyTime) {
      $config = CRM_Core_Config::singleton();
      $dateFormat = $config->dateformatTime;
    }
    if (is_int($dateString)) {
      return CRM_Utils_Date::customFormatTs($dateString, $dateFormat);
    }
    return CRM_Utils_Date::customFormat($dateString, $dateFormat);
  }
  return '';
}
