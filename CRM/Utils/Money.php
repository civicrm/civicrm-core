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

use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\ISOCurrencyProvider;
use Brick\Money\Money;
use Brick\Money\Exception\UnknownCurrencyException;

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
   *
   * @return string
   *   formatted monetary string
   *
   */
  public static function format($amount, $currency = NULL, $format = NULL, $onlyNumber = FALSE) {

    if (CRM_Utils_System::isNull($amount)) {
      return '';
    }

    $config = CRM_Core_Config::singleton();

    if (!$format) {
      $format = $config->moneyformat;
    }

    if (!$currency) {
      $currency = $config->defaultCurrency;
    }

    if ($onlyNumber) {
      $amount = self::formatLocaleNumericRoundedByCurrency($amount, $currency);
      return $amount;
    }

    if (!self::$_currencySymbols) {
      self::$_currencySymbols = CRM_Contribute_DAO_Contribution::buildOptions('currency', 'abbreviate');

    }

    // ensure $currency is a valid currency code
    // for backwards-compatibility, also accept one space instead of a currency
    if ($currency != ' ' && !array_key_exists($currency, self::$_currencySymbols)) {
      throw new CRM_Core_Exception("Invalid currency \"{$currency}\"");
    }

    if ($currency === ' ') {
      CRM_Core_Error::deprecatedWarning('Passing empty currency to CRM_Utils_Money::format is deprecated if you need it for display without currency call CRM_Utils_Money::formatLocaleNumericRounded');
    }
    $amount = self::formatUSLocaleNumericRounded($amount, 2);
    // If it contains tags, means that HTML was passed and the
    // amount is already converted properly,
    // so don't mess with it again.
    // @todo deprecate handling for the html tags because .... WTF
    if (!str_contains($amount, '<')) {
      $amount = self::replaceCurrencySeparators($amount);
    }

    $replacements = [
      '%a' => $amount,
      '%C' => $currency,
      '%c' => self::$_currencySymbols[$currency] ?? $currency,
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
   * Get the currency object for a given
   *
   * Wrapper around the Brick library to support currency codes which Brick doesn't support
   *
   * @internal
   * @param string $currencyCode
   * @return Brick\Money\Currency
   */
  public static function getCurrencyObject(string $currencyCode): Currency {
    try {
      $currency = ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }
    catch (UnknownCurrencyException $e) {
      $currency = new Currency(
        $currencyCode,
        0,
        $currencyCode,
        2
      );
    }

    return $currency;
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
      $currencyObject = self::getCurrencyObject($currency);
      $leftMoney = Money::of($leftOp, $currencyObject, new DefaultContext(), RoundingMode::CEILING);
      $rightMoney = Money::of($rightOp, $currencyObject, new DefaultContext(), RoundingMode::CEILING);
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
   * @param int|float $value1
   * @param int|float $value2
   * @param string $currency
   *
   * @return bool
   */
  public static function equals($value1, $value2, $currency) {
    $precision = pow(10, self::getCurrencyPrecision($currency));

    return (int) round($value1 * $precision) == (int) round($value2 * $precision);
  }

  /**
   * Format money (or number) for display (just numeric part) according to the current or supplied locale.
   *
   * Note this should not be used in conjunction with any calls to
   * replaceCurrencySeparators as this function already does that.
   *
   * @param string $amount
   * @param string $locale
   * @param string $currency
   * @param int $numberOfPlaces
   *
   * @return string
   * @throws \Brick\Money\Exception\UnknownCurrencyException
   */
  protected static function formatLocaleNumeric(string $amount, $locale = NULL, $currency = NULL, $numberOfPlaces = 2): string {
    $currency ??= CRM_Core_Config::singleton()->defaultCurrency;
    $currencyObject = self::getCurrencyObject($currency);
    $money = Money::of($amount, $currencyObject, new CustomContext($numberOfPlaces), RoundingMode::HALF_UP);
    $formatter = new \NumberFormatter($locale ?? CRM_Core_I18n::getLocale(), NumberFormatter::DECIMAL);
    $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $numberOfPlaces);
    return $money->formatWith($formatter);
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
   * @param string|float $amount
   * @param int $numberOfPlaces
   *
   * @return string
   */
  public static function formatUSLocaleNumericRounded($amount, int $numberOfPlaces): string {
    if (!extension_loaded('intl') || !is_numeric($amount)) {
      // @todo - we should not attempt to format non-numeric strings. For now
      // these will not fail but will give notices on php 7.4
      if (!is_numeric($amount)) {
        CRM_Core_Error::deprecatedWarning('Formatting non-numeric values is no longer supported: ' . htmlspecialchars($amount));
      }
      else {
        self::missingIntlNotice();
      }
      return self::formatNumericByFormat($amount, '%!.' . $numberOfPlaces . 'i');
    }
    $currencyObject = self::getCurrencyObject(CRM_Core_Config::singleton()->defaultCurrency);
    $money = Money::of($amount, $currencyObject, new CustomContext($numberOfPlaces), RoundingMode::HALF_UP);
    // @todo - we specify en_US here because we don't want this function to do
    // currency replacement at the moment because
    // formatLocaleNumericRoundedByPrecision is doing it and if it
    // is done there then it is swapped back in there.. This is a short term
    // fix to allow us to resolve formatLocaleNumericRoundedByPrecision
    // and to make the function comments correct - but, we need to reconsider this
    // in master as it is probably better to use locale than our currency separator fields.
    $formatter = new \NumberFormatter('en_US', NumberFormatter::DECIMAL);
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
    $amount = self::formatUSLocaleNumericRounded($amount, $precision);
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
    $decimalPlaces = self::getDecimalPlacesForAmount((string) $amount);
    $amount = self::formatUSLocaleNumericRounded($amount, $precision > $decimalPlaces ? $decimalPlaces : $precision);
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
  protected static function formatNumericByFormat($amount, $valueFormat = '%!i') {
    // money_format() exists only in certain PHP install (CRM-650)
    // setlocale() affects native gettext (CRM-11054, CRM-9976)
    if (is_numeric($amount) && function_exists('money_format')) {
      $lc = setlocale(LC_MONETARY, 0);
      setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
      // phpcs:disable
      $amount = money_format($valueFormat, $amount);
      // phpcs:enable
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

  /**
   * Get the number of characters after the decimal point.
   *
   * @param string $amount
   *
   * @return int
   */
  protected static function getDecimalPlacesForAmount(string $amount): int {
    $decimalPlaces = strlen(substr($amount, strpos($amount, '.') + 1));
    return $decimalPlaces;
  }

}
