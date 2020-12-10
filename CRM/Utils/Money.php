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

use Brick\Money\Money;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\CustomContext;
use Brick\Math\RoundingMode;

/**
 * Money utilties
 */
class CRM_Utils_Money {
  public static $_currencySymbols = NULL;

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

    if (!empty($valueFormat) && $valueFormat !== '%!i') {
      CRM_Core_Error::deprecatedWarning('Having a Money Value format other than %!i is deprecated, please report this on the GitLab Issue https://lab.civicrm.org/dev/core/-/issues/1494 with the relevant moneyValueFormat you use.');
    }

    if (!$currency) {
      $currency = $config->defaultCurrency;
    }

    if ($onlyNumber) {
      $amount = self::formatLocaleNumericRoundedByCurrency($amount, $currency);
      return $amount;
    }

    if (!self::$_currencySymbols) {
      self::$_currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', [
        'keyColumn' => 'name',
        'labelColumn' => 'symbol',
      ]);
    }

    // ensure $currency is a valid currency code
    // for backwards-compatibility, also accept one space instead of a currency
    if ($currency != ' ' && !array_key_exists($currency, self::$_currencySymbols)) {
      throw new CRM_Core_Exception("Invalid currency \"{$currency}\"");
    }

    if ($currency === ' ') {
      CRM_Core_Error::deprecatedWarning('Passing empty currency to CRM_Utils_Money::format is deprecated if you need it for display without currency call CRM_Utils_Money::formatLocaleNumericRounded');
    }

    $amount = self::formatNumericByFormat($amount, $valueFormat);
    // If it contains tags, means that HTML was passed and the
    // amount is already converted properly,
    // so don't mess with it again.
    // @todo deprecate handling for the html tags because .... WTF
    if (strpos($amount, '<') === FALSE) {
      $amount = self::replaceCurrencySeparators($amount);
    }

    $replacements = [
      '%a' => $amount,
      '%C' => $currency,
      '%c' => CRM_Utils_Array::value($currency, self::$_currencySymbols, $currency),
    ];
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
   * @param string|float $leftOp
   * @param string|float $rightOp
   * @param string $currency
   *
   * @return float
   *   Result of subtracting $rightOp from $leftOp to the precision of $currency
   */
  public static function subtractCurrencies($leftOp, $rightOp, $currency) {
    if (is_numeric($leftOp) && is_numeric($rightOp)) {
      $leftMoney = Money::of($leftOp, $currency, new DefaultContext(), RoundingMode::CEILING);
      $rightMoney = Money::of($rightOp, $currency, new DefaultContext(), RoundingMode::CEILING);
      return $leftMoney->minus($rightMoney)->getAmount()->toFloat();
    }
  }

  /**
   * Tests if two currency values are equal, taking into account the currency's
   * precision, so that the two values are compared as integers after rounding.
   *
   * Eg.
   *
   * 1.231 == 1.232 with a currency precision of 2 decimal points
   * 1.234 != 1.236 with a currency precision of 2 decimal points
   * 1.300 != 1.200 with a currency precision of 2 decimal points
   *
   * @param $value1
   * @param $value2
   * @param $currency
   *
   * @return bool
   */
  public static function equals($value1, $value2, $currency) {
    $precision = pow(10, self::getCurrencyPrecision($currency));

    return (int) round($value1 * $precision) == (int) round($value2 * $precision);
  }

  /**
   * Format money for display (just numeric part) according to the current locale.
   *
   * This calls the underlying system function but does not handle currency separators.
   *
   * It's not totally clear when it changes the $amount value but has historical usage.
   *
   * @param $amount
   *
   * @return string
   */
  protected static function formatLocaleNumeric($amount) {
    if (CRM_Core_Config::singleton()->moneyvalueformat !== '%!i') {
      CRM_Core_Error::deprecatedWarning('Having a Money Value format other than !%i is deprecated, please report this on GitLab with the relevant moneyValueFormat you use.');
    }
    return self::formatNumericByFormat($amount, CRM_Core_Config::singleton()->moneyvalueformat);
  }

  /**
   * Format money for display (just numeric part) according to the current locale with rounding.
   *
   * At this stage this is conceived as an internal function with the currency wrapper
   * functions determining the number of places.
   *
   * This calls the underlying system function but does not handle currency separators.
   *
   * It's not totally clear when it changes the $amount value but has historical usage.
   *
   * @param string $amount
   * @param int $numberOfPlaces
   *
   * @return string
   */
  protected static function formatLocaleNumericRounded($amount, $numberOfPlaces) {
    if (!extension_loaded('intl')) {
      self::missingIntlNotice();
      return self::formatNumericByFormat($amount, '%!.' . $numberOfPlaces . 'i');
    }
    $money = Money::of($amount, CRM_Core_Config::singleton()->defaultCurrency, new CustomContext($numberOfPlaces), RoundingMode::CEILING);
    $formatter = new \NumberFormatter(CRM_Core_I18n::getLocale(), NumberFormatter::CURRENCY);
    $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, '');
    $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $numberOfPlaces);
    return $money->formatWith($formatter);
  }

  /**
   * Format money for display (just numeric part) according to the current locale with rounding.
   *
   * This handles both rounding & replacement of the currency separators for the locale.
   *
   * @param string $amount
   * @param string $currency
   *
   * @return string
   *   Formatted amount.
   */
  public static function formatLocaleNumericRoundedByCurrency($amount, $currency) {
    return self::formatLocaleNumericRoundedByPrecision($amount, self::getCurrencyPrecision($currency));
  }

  /**
   * Format money for display (just numeric part) according to the current locale with rounding to the supplied precision.
   *
   * This handles both rounding & replacement of the currency separators for the locale.
   *
   * @param string $amount
   * @param int $precision
   *
   * @return string
   *   Formatted amount.
   */
  public static function formatLocaleNumericRoundedByPrecision($amount, $precision) {
    $amount = self::formatLocaleNumericRounded($amount, $precision);
    return self::replaceCurrencySeparators($amount);
  }

  /**
   * Format money for display with rounding to the supplied precision but without padding.
   *
   * If the string is shorter than the precision trailing zeros are not added to reach the precision
   * beyond the 2 required for normally currency formatting.
   *
   * This handles both rounding & replacement of the currency separators for the locale.
   *
   * @param string $amount
   * @param int $precision
   *
   * @return string
   *   Formatted amount.
   */
  public static function formatLocaleNumericRoundedByOptionalPrecision($amount, $precision) {
    $decimalPlaces = strlen(substr($amount, strpos($amount, '.') + 1));
    $amount = self::formatLocaleNumericRounded($amount, $precision > $decimalPlaces ? $decimalPlaces : $precision);
    return self::replaceCurrencySeparators($amount);
  }

  /**
   * Format money for display (just numeric part) according to the current locale with rounding based on the
   * default currency for the site.
   *
   * @param $amount
   * @return mixed
   */
  public static function formatLocaleNumericRoundedForDefaultCurrency($amount) {
    return self::formatLocaleNumericRoundedByCurrency($amount, self::getCurrencyPrecision(CRM_Core_Config::singleton()->defaultCurrency));
  }

  /**
   * Replace currency separators.
   *
   * @param string $amount
   *
   * @return string
   */
  protected static function replaceCurrencySeparators($amount) {
    $config = CRM_Core_Config::singleton();
    $rep = [
      ',' => $config->monetaryThousandSeparator,
      '.' => $config->monetaryDecimalPoint,
    ];
    return strtr($amount, $rep);
  }

  /**
   * Format numeric part of currency by the passed in format.
   *
   * This is envisaged as an internal function, with wrapper functions defining valueFormat
   * into easily understood functions / variables and handling separator conversions and
   * rounding.
   *
   * @param string $amount
   * @param string $valueFormat
   *
   * @return string
   */
  protected static function formatNumericByFormat($amount, $valueFormat) {
    // money_format() exists only in certain PHP install (CRM-650)
    // setlocale() affects native gettext (CRM-11054, CRM-9976)
    if (is_numeric($amount) && function_exists('money_format')) {
      $lc = setlocale(LC_MONETARY, 0);
      setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
      $amount = money_format($valueFormat, $amount);
      setlocale(LC_MONETARY, $lc);
    }
    return $amount;
  }

  /**
   * Emits a notice indicating we have fallen back to a less accurate way of formatting money due to missing intl extension
   */
  public static function missingIntlNotice() {
    CRM_Core_Session::singleton()->setStatus(ts('As this system does not include the PHP intl extension, CiviCRM has fallen back onto a slightly less accurate and deprecated method to format money'), ts('Missing PHP INTL extension'));
  }

}
