<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * Money utilties
 */
class CRM_Utils_Money {
  static $_currencySymbols = NULL;

  /**
   * Warn if php money_format() doesn't exist as they are likely to experience issues displaying currency.
   * @return bool
   */
  private static function moneyFormatExists() {
    // money_format() exists only in certain PHP install (CRM-650)
    if (!function_exists('money_format')) {
      Civi::log()->warning('PHP money_format function does not exist. Monetary amounts may not format correctly for display.');
      return FALSE;
    };
    return TRUE;
  }

  /**
   * FIXME: This should probably be changed
   * @param $amount
   *
   * @return mixed
   */
  public static function formatLongDecimal($amount) {
    return filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  }

  /**
   * Format money for display (just numeric part) according to the current locale
   *
   * @param $amount
   *
   * @return string
   */
  public static function formatLocaleNumeric($amount) {
    $config = CRM_Core_Config::singleton();
    $format = $config->moneyvalueformat;
    return self::formatNumeric($amount, $format);
  }

  /**
   * Format money for display (just numeric part). Specify format or use formatLocaleNumeric() instead.
   *
   * @param $amount
   * @param $valueFormat
   *
   * @return string
   */
  public static function formatNumeric($amount, $valueFormat) {
    if (CRM_Utils_System::isNull($amount)) {
      return '';
    }

    $moneyFormatExists = self::moneyFormatExists();
    if (is_numeric($amount) && $moneyFormatExists) {
      $lc = setlocale(LC_MONETARY, 0);
      setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
      $amount = money_format($valueFormat, $amount);
      setlocale(LC_MONETARY, $lc);
    }
    return $amount;
  }

  /**
   * Format money for display (with symbols etc) according to the current locale
   * @param $amount
   * @param null $currency
   *
   * @return string
   */
  public static function formatLocaleFull($amount, $currency = NULL) {
    $config = CRM_Core_Config::singleton();
    $format = $config->moneyformat;
    $valueFormat = $config->moneyvalueformat;
    return self::formatFull($amount, $format, $valueFormat, $currency);
  }

  /**
   * Format money for display (with symbols etc). Specify format or use formatLocaleFull() instead.
   * @param $amount
   * @param $format
   * @param $valueFormat
   * @param null $currency
   *
   * @return string
   */
  public static function formatFull($amount, $format, $valueFormat, $currency = NULL) {
    if (CRM_Utils_System::isNull($amount)) {
      return '';
    }

    // If it contains tags, means that HTML was passed and the
    // amount is already converted properly, so don't mess with it again.
    if (strpos($amount, '<') !== FALSE) {
      return $amount;
    }

    if (!self::$_currencySymbols) {
      self::$_currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array(
        'keyColumn' => 'name',
        'labelColumn' => 'symbol',
      ));
    }

    if (!$currency) {
      $config = CRM_Core_Config::singleton();
      $currency = $config->defaultCurrency;
    }

    $moneyFormatExists = self::moneyFormatExists();
    // setlocale() affects native gettext (CRM-11054, CRM-9976)
    if (is_numeric($amount) && $moneyFormatExists) {
      $lc = setlocale(LC_MONETARY, 0);
      setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
      $amount = money_format($valueFormat, $amount);
      setlocale(LC_MONETARY, $lc);
    }

    // Replace separators
    $rep = array(
      ',' => $config->monetaryThousandSeparator,
      '.' => $config->monetaryDecimalPoint,
    );
    $amount = strtr($amount, $rep);

    // Final formatting
    $replacements = array(
      '%a' => $amount,
      '%C' => $currency,
      '%c' => CRM_Utils_Array::value($currency, self::$_currencySymbols, $currency),
    );
    return strtr($format, $replacements);
  }

  /**
   * @deprecated Format a monetary string.
   * Replaced by multiple different functions above
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

}
