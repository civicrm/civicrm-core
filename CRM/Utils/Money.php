<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Money utilties
 */
class CRM_Utils_Money {
  static $_currencySymbols = NULL;

  /**
   * Format a monetary string.
   *
   * Format a monetary string basing on the amount provided,
   * ISO currency code provided and a format string consisting of:
   *
   * %a - the formatted amount
   * %C - the currency ISO code (e.g., 'USD') if provided
   * %c - the currency symbol (e.g., '$') if available
   *
   * @param float $amount
   *   The monetary amount to display (1234.56).
   * @param string $currency
   *   The three-letter ISO currency code ('USD').
   * @param string $format
   *   The desired currency format.
   * @param bool $onlyNumber
   * @param string $valueFormat
   *   The desired monetary value display format (e.g. '%!i').
   *
   * @return string
   *   formatted monetary string
   *
   */
  public static function format($amount, $currency = NULL, $format = NULL, $onlyNumber = FALSE, $valueFormat = NULL) {

    if (CRM_Utils_System::isNull($amount)) {
      return '';
    }

    $config = CRM_Core_Config::singleton();

    if (!$format) {
      $format = $config->moneyformat;
    }

    if (!$valueFormat) {
      $valueFormat = $config->moneyvalueformat;
    }

    if ($onlyNumber) {
      // money_format() exists only in certain PHP install (CRM-650)
      if (is_numeric($amount) and function_exists('money_format')) {
        $amount = money_format($valueFormat, $amount);
      }
      return $amount;
    }

    if (!self::$_currencySymbols) {
      self::$_currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array(
          'keyColumn' => 'name',
          'labelColumn' => 'symbol',
        ));
    }

    if (!$currency) {
      $currency = $config->defaultCurrency;
    }

    // money_format() exists only in certain PHP install (CRM-650)
    // setlocale() affects native gettext (CRM-11054, CRM-9976)
    if (is_numeric($amount) && function_exists('money_format')) {
      $lc = setlocale(LC_MONETARY, 0);
      setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
      $amount = money_format($valueFormat, $amount);
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

  /**
   * This is a placeholder function for calculating the number of decimal places for a currency.
   *
   * Currently code assumes 2 decimal places but some currencies (bitcoin, middle eastern) have
   * more. By using this function we can signpost the locations where the number of decimal places is
   * currency specific for future enhancement.
   *
   * @param string $currency
   *
   * @return int
   *   Number of decimal places.
   */
  public static function getCurrencyPrecision($currency = NULL) {
    return 2;
  }

  /**
   * Subtract currencies using integers instead of floats, to preserve precision
   *
   * @return float
   *   Result of subtracting $rightOp from $leftOp to the precision of $currency
   */
  public static function subtractCurrencies($leftOp, $rightOp, $currency) {
    if (is_numeric($leftOp) && is_numeric($rightOp)) {
      $precision = pow(10, self::getCurrencyPrecision($currency));
      return (($leftOp * $precision) - ($rightOp * $precision)) / $precision;
    }
  }

  /**
   * Tests if two currency values are equal, taking into account the currency's
   * precision, so that if the difference between the two values is less than
   * one more order of magnitude for the precision, then the values are
   * considered as equal. So, if the currency has  precision of 2 decimal
   * points, a difference of more than 0.001 will cause the values to be
   * considered as different. Anything less than 0.001 will be considered as
   * equal.
   *
   * Eg.
   *
   * 1.2312 == 1.2319 with a currency precision of 2 decimal points
   * 1.2310 != 1.2320 with a currency precision of 2 decimal points
   * 1.3000 != 1.2000 with a currency precision of 2 decimal points
   *
   * @param $value1
   * @param $value2
   * @param $currency
   *
   * @return bool
   */
  public static function equals($value1, $value2, $currency) {
    $precision = 1 / pow(10, self::getCurrencyPrecision($currency) + 1);
    $difference = self::subtractCurrencies($value1, $value2, $currency);

    if (abs($difference) > $precision) {
      return FALSE;
    }

    return TRUE;
  }

}
