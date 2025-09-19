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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_Number
 */
class CRM_Utils_Number {

  /**
   * Create a random number with a given precision.
   *
   * @param array $precision
   *   (int $significantDigits, int $postDecimalDigits).
   *
   * @return float
   *
   * @link https://dev.mysql.com/doc/refman/5.1/en/fixed-point-types.html
   */
  public static function createRandomDecimal($precision) {
    [$sigFigs, $decFigs] = $precision;
    $rand = rand(0, pow(10, $sigFigs) - 1);
    return $rand / pow(10, $decFigs);
  }

  /**
   * Given a number, coerce it to meet the precision requirement. If possible, it should
   * keep the number as-is. If necessary, this may drop the least-significant digits
   * and/or move the decimal place.
   *
   * @param int|float $keyValue
   * @param array $precision
   *   (int $significantDigits, int $postDecimalDigits).
   * @return float
   * @link https://dev.mysql.com/doc/refman/5.1/en/fixed-point-types.html
   */
  public static function createTruncatedDecimal($keyValue, $precision) {
    [$sigFigs, $decFigs] = $precision;
    $sign = ($keyValue < 0) ? '-1' : 1;
    // ex: -123.456 ==> 123456
    $val = str_replace('.', '', abs($keyValue));
    // ex: 123456 => 1234
    $val = substr($val, 0, $sigFigs);

    // Move any extra digits after decimal
    $extraFigs = strlen($val) - ($sigFigs - $decFigs);
    if ($extraFigs > 0) {
      // ex: 1234 => 1.234
      return $sign * $val / pow(10, $extraFigs);
    }
    else {
      return $sign * $val;
    }
  }

  /**
   * Convert a file size value from the formats allowed in php_ini to the number of bytes.
   *
   * @param string $size
   *
   * @return int
   */
  public static function formatUnitSize($size): int {
    if ($size) {
      $last = strtolower($size[strlen($size) - 1]);
      $size = (int) $size;
      switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0

        case 'g':
          $size *= 1024;
        case 'm':
          $size *= 1024;
        case 'k':
          $size *= 1024;
      }
      return $size;
    }
  }

  /**
   * Get the maximum size permitted for a file upload.
   *
   * @return float
   */
  public static function getMaximumFileUploadSize(): float {
    $uploadFileSize = \CRM_Utils_Number::formatUnitSize(\Civi::settings()->get('maxFileSize') . 'm', TRUE);
    //Fetch uploadFileSize from php_ini when $config->maxFileSize is set to "no limit".
    if (empty($uploadFileSize)) {
      $uploadFileSize = \CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'), TRUE);
    }
    return round(($uploadFileSize / (1024 * 1024)), 2);
  }

  /**
   * Format number for display according to the current or supplied locale.
   *
   * Note this should not be used in conjunction with any calls to
   * replaceCurrencySeparators as this function already does that.
   *
   * @param string $amount
   * @param string $locale
   * @param int[] $attributes
   *   Options passed to NumberFormatter::setAttribute
   *   see https://www.php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
   *
   * @return string
   */
  public static function  formatLocaleNumeric(string $amount, $locale = NULL, array $attributes = []): string {
    if ($amount === "") {
      CRM_Core_Error::deprecatedWarning('Passing an empty string for amount is deprecated.');
      return $amount;
    }

    $formatter = new \NumberFormatter($locale ?? CRM_Core_I18n::getLocale(), NumberFormatter::DECIMAL);
    $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, CRM_Core_Config::singleton()->monetaryDecimalPoint);
    $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, CRM_Core_Config::singleton()->monetaryThousandSeparator);

    foreach ($attributes as $key => $value) {
      $formatter->setAttribute($key, (int) $value);
    }

    return $formatter->format($amount);
  }

}
