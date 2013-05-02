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
 * Money utilties
 */
class CRM_Utils_Money {
  static $_currencySymbols = NULL;

  /**
   * format a monetary string
   *
   * Format a monetary string basing on the amount provided,
   * ISO currency code provided and a format string consisting of:
   *
   * %a - the formatted amount
   * %C - the currency ISO code (e.g., 'USD') if provided
   * %c - the currency symbol (e.g., '$') if available
   *
   * @param float  $amount    the monetary amount to display (1234.56)
   * @param string $currency  the three-letter ISO currency code ('USD')
   * @param string $format    the desired currency format
   *
   * @return string  formatted monetary string
   *
   * @static
   */
  static function format($amount, $currency = NULL, $format = NULL, $onlyNumber = FALSE) {

    if (CRM_Utils_System::isNull($amount)) {
      return '';
    }

    $config = CRM_Core_Config::singleton();

    if (!$format) {
      $format = $config->moneyformat;
    }

    if ($onlyNumber) {
      // money_format() exists only in certain PHP install (CRM-650)
      if (is_numeric($amount) and function_exists('money_format')) {
        $amount = money_format($config->moneyvalueformat, $amount);
      }
      return $amount;
    }

    if (!self::$_currencySymbols) {
      $currencySymbolName = CRM_Core_PseudoConstant::currencySymbols('name');
      $currencySymbol = CRM_Core_PseudoConstant::currencySymbols();

      self::$_currencySymbols = array_combine($currencySymbolName, $currencySymbol);
    }

    if (!$currency) {
      $currency = $config->defaultCurrency;
    }

    if (!$format) {
      $format = $config->moneyformat;
    }

    // money_format() exists only in certain PHP install (CRM-650)
    // setlocale() affects native gettext (CRM-11054, CRM-9976)
    if (is_numeric($amount) && function_exists('money_format')) {
      $lc = setlocale(LC_MONETARY, 0);
      setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
      $amount = money_format($config->moneyvalueformat, $amount);
      setlocale(LC_MONETARY, $lc);
    }

    $rep = array(
      ',' => $config->monetaryThousandSeparator,
      '.' => $config->monetaryDecimalPoint,
    );

    // If it contains tags, means that HTML was passed and the
    // amount is already converted properly,
    // so don't mess with it again.
    if (strpos($amount, '<') === FALSE) {
      $amount = strtr($amount, $rep);
    }

    $replacements = array(
      '%a' => $amount,
      '%C' => $currency,
      '%c' => CRM_Utils_Array::value($currency, self::$_currencySymbols, $currency),
    );
    return strtr($format, $replacements);
  }
}

