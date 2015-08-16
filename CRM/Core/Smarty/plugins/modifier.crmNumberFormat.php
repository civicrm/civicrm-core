<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
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
function smarty_modifier_crmNumberFormat($number, $decimals = NULL, $dec_point = NULL, $thousands_sep = NULL) {
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
