<?php

/**
 * Class CRM_Utils_Number
 */
class CRM_Utils_Number {
  /**
   * Create a random number with a given precision
   *
   * @param array $precision (int $significantDigits, int $postDecimalDigits)
   *
   * @return float
   * @link https://dev.mysql.com/doc/refman/5.1/en/fixed-point-types.html
   */
  static function createRandomDecimal($precision) {
    list ($sigFigs, $decFigs) = $precision;
    $rand = rand(0, pow(10, $sigFigs) - 1);
    return $rand / pow(10, $decFigs);
  }

  /**
   * Given a number, coerce it to meet the precision requirement. If possible, it should
   * keep the number as-is. If necessary, this may drop the least-significant digits
   * and/or move the decimal place.
   *
   * @param int|float $keyValue
   * @param array $precision (int $significantDigits, int $postDecimalDigits)
   * @return float
   * @link https://dev.mysql.com/doc/refman/5.1/en/fixed-point-types.html
   */
  static function createTruncatedDecimal($keyValue, $precision) {
    list ($sigFigs, $decFigs) = $precision;
    $sign = ($keyValue < 0) ? '-1' : 1;
    $val = str_replace('.', '', abs($keyValue)); // ex: -123.456 ==> 123456
    $val = substr($val, 0, $sigFigs); // ex: 123456 => 1234

    // Move any extra digits after decimal
    $extraFigs = strlen($val) - ($sigFigs - $decFigs);
    if ($extraFigs > 0) {
      return $sign * $val / pow(10, $extraFigs); // ex: 1234 => 1.234
    }
    else {
      return $sign * $val;
    }
  }
}
