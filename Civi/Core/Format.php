<?php

namespace Civi\Core;

use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Civi;
use CRM_Core_Config;
use CRM_Core_I18n;
use CRM_Utils_Constant;
use CRM_Utils_Money;
use NumberFormatter;
use Brick\Money\Context\AutoContext;

/**
 * Class Paths
 * @package Civi\Core
 * @service format
 *
 * This class provides standardised formatting
 */
class Format extends \Civi\Core\Service\AutoService {

  /**
   * Get formatted money
   *
   * @param string|int|float|BigDecimal $amount
   * @param string|null $currency
   *   Currency, defaults to site currency if not provided.
   * @param string|null $locale
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function money($amount, ?string $currency = NULL, ?string $locale = NULL): string {
    if (($amount = $this->checkAndConvertAmount($amount)) === '') {
      return '';
    }
    if (!$currency) {
      $currency = Civi::settings()->get('defaultCurrency');
    }
    $currencyObject = CRM_Utils_Money::getCurrencyObject($currency);
    $money = Money::of($amount, $currencyObject, NULL, RoundingMode::HALF_UP);
    $formatter = $this->getMoneyFormatter($currency, $locale);
    return $money->formatWith($formatter);
  }

  /**
   * Get a formatted number.
   *
   * @param string|int|float|Money $amount
   *   Amount in a machine money format.
   * @param string|null $locale
   * @param array $attributes
   *   Additional values supported by NumberFormatter
   *   https://www.php.net/manual/en/class.numberformatter.php
   *   By default this will set it to round to 8 places and not
   *   add any padding.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function number($amount, ?string $locale = NULL, array $attributes = [
    NumberFormatter::MIN_FRACTION_DIGITS => 0,
    NumberFormatter::MAX_FRACTION_DIGITS => 8,
  ]): string {
    if (($amount = $this->checkAndConvertAmount($amount)) === '') {
      return '';
    }
    $formatter = $this->getMoneyFormatter(NULL, $locale, NumberFormatter::DECIMAL, $attributes);
    return $formatter->format($amount);
  }

  /**
   * Get a number formatted with rounding expectations derived from the currency.
   *
   * @param string|float|int $amount
   * @param string $currency
   * @param string|null $locale
   *
   * @return string
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function moneyNumber($amount, string $currency, $locale = NULL): string {
    if (($amount = $this->checkAndConvertAmount($amount)) === '') {
      return '';
    }
    $formatter = $this->getMoneyFormatter($currency, $locale, NumberFormatter::DECIMAL);
    $currencyObject = CRM_Utils_Money::getCurrencyObject($currency);
    return Money::of($amount, $currencyObject, NULL, RoundingMode::HALF_UP)->formatWith($formatter);
  }

  /**
   * Get a number formatted to a machine format with padded decimal places.
   *
   * This is intended to be a machine-friendly format that is also suitable
   * for sending out to other systems that might expect 2 digits after the
   * decimal point.
   *
   * Most currencies format to 2 decimal places so the default of 'USD' will
   * achieve that.
   *
   * For example an input of 1000.1 will return 1000.10.
   *
   * This will ensure that
   *
   * @param string|float|int $amount
   * @param string $currency
   *
   * @return string
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function machineMoney($amount, string $currency = 'USD'): string {
    $formatter = $this->getMoneyFormatter($currency, 'en_US', NumberFormatter::DECIMAL, [NumberFormatter::GROUPING_USED => FALSE]);
    $currencyObject = CRM_Utils_Money::getCurrencyObject($currency);
    return Money::of($amount, $currencyObject, NULL, RoundingMode::HALF_UP)->formatWith($formatter);
  }

  /**
   * Get a money value with formatting but not rounding.
   *
   * @param string|float|int $amount
   * @param string|null $currency
   * @param string|null $locale
   *
   * @return string
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function moneyLong($amount, ?string $currency, ?string $locale): string {
    if (($amount = $this->checkAndConvertAmount($amount)) === '') {
      return '';
    }
    $formatter = $this->getMoneyFormatter($currency, $locale, NumberFormatter::CURRENCY, [
      NumberFormatter::MAX_FRACTION_DIGITS => 9,
    ]);
    $currencyObject = CRM_Utils_Money::getCurrencyObject($currency);
    $money = Money::of($amount, $currencyObject, new AutoContext());
    return $money->formatWith($formatter);
  }

  /**
   * Get a number with minimum decimal places based on the currency but no rounding.
   *
   * @param string|float|int $amount
   * @param string|null $currency
   * @param string|null $locale
   *
   * @return string
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function moneyNumberLong($amount, ?string $currency, ?string $locale): string {
    if (($amount = $this->checkAndConvertAmount($amount)) === '') {
      return '';
    }
    $formatter = $this->getMoneyFormatter($currency, $locale, NumberFormatter::DECIMAL, [
      NumberFormatter::MAX_FRACTION_DIGITS => 9,
    ]);
    $currencyObject = CRM_Utils_Money::getCurrencyObject($currency);
    $money = Money::of($amount, $currencyObject, new AutoContext());
    return $money->formatWith($formatter);
  }

  /**
   * Should we use the configured thousand & decimal separators.
   *
   * Historically, separators were configured with system-settings, but this is problematic
   * when you have multiple locales with divergent separator rules (eg en_GB / fr_FR).
   * The goal is to transition to locale-based-separators instead of setting-based-separators;
   * but for now, we only switch if there's an opt-in flag.
   */
  protected function isUseSeparatorSettings(): bool {
    // If system enables `format_locale`, then abide the specified locale (regardless of old separator settings).
    // If system enables IGNORE_SEPARATOR_CONFIG, then abide the active locale's spec (regardless of old separator settings).
    // If system enables `partial_locales`, then abide active locale's spec (regardless of old separator settings).
    return !Civi::settings()->get('format_locale')
      && !CRM_Utils_Constant::value('IGNORE_SEPARATOR_CONFIG')
      && !Civi::settings()->get('partial_locales');
  }

  /**
   * Get the cached money formatter.
   *
   * @param string|null $currency
   * @param string|null $locale
   * @param int $style
   *   See https://www.php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants
   * @param array $attributes
   *   See https://www.php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
   *
   * @return \NumberFormatter
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getMoneyFormatter(?string $currency = NULL, ?string $locale = NULL, int $style = NumberFormatter::CURRENCY, array $attributes = []): NumberFormatter {
    if (!$currency) {
      $currency = Civi::settings()->get('defaultCurrency');
    }
    if (!isset($locale)) {
      global $civicrmLocale;
      $locale = $civicrmLocale->moneyFormat ?? (Civi::settings()->get('format_locale') ?? CRM_Core_I18n::getLocale());
    }

    $cacheKey = __CLASS__ . $currency . '_' . $locale . '_' . $style . (!empty($attributes) ? md5(json_encode($attributes)) : '');
    if (!isset(\Civi::$statics[$cacheKey])) {
      $formatter = new NumberFormatter($locale, $style);

      $currencyObject = CRM_Utils_Money::getCurrencyObject($currency);
      if (!isset($attributes[NumberFormatter::MIN_FRACTION_DIGITS])) {
        $attributes[NumberFormatter::MIN_FRACTION_DIGITS] = $currencyObject->getDefaultFractionDigits();
      }
      if (!isset($attributes[NumberFormatter::MAX_FRACTION_DIGITS])) {
        $attributes[NumberFormatter::MAX_FRACTION_DIGITS] = $currencyObject->getDefaultFractionDigits();
      }

      foreach ($attributes as $attribute => $value) {
        $formatter->setAttribute($attribute, $value);
      }
      if ($locale === CRM_Core_I18n::getLocale() && $this->isUseSeparatorSettings()) {
        $formatter->setSymbol(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, CRM_Core_Config::singleton()->monetaryThousandSeparator);
        $formatter->setSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL, CRM_Core_Config::singleton()->monetaryDecimalPoint);
      }
      \Civi::$statics[$cacheKey] = $formatter;
    }
    return \Civi::$statics[$cacheKey];
  }

  /**
   * Since the input can be various data types and values, we need to handle
   * them before passing on to the Brick libraries which would throw exceptions
   * for ones that we are ok just converting to the empty string.
   *
   * @param string|int|float|BigDecimal $amount
   * @return string
   *   Either the empty string if an empty-ish value, or the original amount as a string.
   * @throws \CRM_Core_Exception
   */
  private function checkAndConvertAmount($amount): string {
    // Empty value => empty string
    // FALSE should be an error but some smarty variables are filled with FALSE to avoid ENOTICES.
    if (is_null($amount) || $amount === '' || $amount === FALSE) {
      return '';
    }
    // Verify the amount is a number or numeric string/object.
    // We cast to string because it can be a BigDecimal object.
    if ($amount === TRUE || !is_numeric((string) $amount)) {
      throw new \CRM_Core_Exception('Invalid value for type money');
    }
    return (string) $amount;
  }

}
