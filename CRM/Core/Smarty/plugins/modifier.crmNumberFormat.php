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
 * Add thousands separator to numeric strings using
 * PHP number_format() function.
 *
 * @param float $number
 *   Numeric value to be formatted.
 * @param int $decimals
 *   Number of decimal places.
 * @param string $dec_point
 *   Decimal point character (if other than ".").
 * @param string $thousands_sep
 *   Thousands sep character (if other than ",").
 *
 * @return string
 *   the formatted string
 *
 *   For alternate decimal point and thousands separator, delimit values with single quotes in the template.
 *   EXAMPLE:   {$number|crmNumberFormat:2:',':' '} for French notation - 1234.56 becomes 1 234,56
 */
function smarty_modifier_crmNumberFormat($number, $decimals = 0, $dec_point = NULL, $thousands_sep = NULL) {
  if (is_numeric($number)) {
    // Both dec_point AND thousands_sep are required if one is not specified
    // then use the config defaults
    if (!$dec_point || !$thousands_sep) {
      $config = CRM_Core_Config::singleton();
      $dec_point = $config->monetaryDecimalPoint;
      $thousands_sep = $config->monetaryThousandSeparator;
    }

    return number_format($number, $decimals, $dec_point, $thousands_sep);
  }

  return '';
}
