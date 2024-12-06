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
 * Utilities for rendering numbers as English.
 *
 * Note: This file may be used in a standalone environment. Please ensure it
 * remains self-sufficient (without needing any external services).
 */
class CRM_Utils_EnglishNumber {

  protected static $lowNumbers = [
    'Zero',
    'One',
    'Two',
    'Three',
    'Four',
    'Five',
    'Six',
    'Seven',
    'Eight',
    'Nine',
    'Ten',
    'Eleven',
    'Twelve',
    'Thirteen',
    'Fourteen',
    'Fifteen',
    'Sixteen',
    'Seventeen',
    'Eighteen',
    'Nineteen',
  ];

  protected static $intervalsOfTen = [
    9 => 'Ninety',
    8 => 'Eighty',
    7 => 'Seventy',
    6 => 'Sixty',
    5 => 'Fifty',
    4 => 'Forty',
    3 => 'Thirty',
    2 => 'Twenty',
  ];

  /**
   * @param int $num
   *   Ex: 12 or 54.
   * @param mixed $default
   *   The default value to return if we cannot determine an English representation.
   *   If omitted or NULL, throws an exception.
   *   Tip: If you want to support high values as numerals, just pass the number again.
   * @return string
   *   Ex: 'Twelve' or 'FiftyFour'.
   */
  public static function toCamelCase($num, $default = NULL) {
    $hundreds = (int) ($num / 100);
    $num = ($num % 100);
    $tens = (int) ($num / 10);
    $last = $num % 10;

    $prefix = '';
    if ($hundreds > 0) {
      $prefix = static::toCamelCase($hundreds);
      if ($tens === 0 && $last === 0) {
        return $prefix . 'Hundred';
      }
      elseif ($tens === 0) {
        $prefix .= 'Oh';
      }
    }

    if (isset(self::$lowNumbers[$num])) {
      return $prefix . self::$lowNumbers[$num];
    }

    if (isset(self::$intervalsOfTen[$tens])) {
      if ($last == 0) {
        return $prefix . self::$intervalsOfTen[$tens];
      }
      else {
        return $prefix . self::$intervalsOfTen[$tens] . self::$lowNumbers[$last];
      }
    }

    if ($default === NULL) {
      throw new \RuntimeException("Cannot convert number to English: " . (int) $num);
    }
    else {
      return $default;
    }
  }

  /**
   * @param int $num
   *   Ex: 12 or 54.
   * @param mixed $default
   *   The default value to return if we cannot determine an English representation.
   *   If omitted or NULL, throws an exception.
   *   Tip: If you want to support high values as numerals, just pass the number again.
   * @return string
   *   Ex: 'twelve' or 'fifty-four'.
   */
  public static function toHyphen($num, $default = NULL) {
    $camel = static::toCamelCase($num, $default);
    return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $camel));
  }

  /**
   * Convert an English-style number to an int.
   *
   * @param string $english
   *   Ex: 'TwentyTwo' or 'forty-four'
   *
   * @return int
   *   22 or 44
   */
  public static function toInt(string $english) {
    $intBuf = 0;
    $strBuf = strtolower(str_replace('-', '', $english));

    foreach (self::$intervalsOfTen as $num => $name) {
      if (str_starts_with($strBuf, strtolower($name))) {
        $intBuf += 10 * $num;
        $strBuf = substr($strBuf, strlen($name));
        break;
      }
    }
    foreach (array_reverse(self::$lowNumbers, TRUE) as $num => $name) {
      if (str_starts_with($strBuf, strtolower($name))) {
        $intBuf += $num;
        $strBuf = substr($strBuf, strlen($name));
        break;
      }
    }

    if (!empty($strBuf)) {
      throw new InvalidArgumentException("Failed to parse english number: $strBuf");
    }

    return $intBuf;
  }

  /**
   * Determine if a string looks like
   *
   * @param string $english
   *
   * @return bool
   */
  public static function isNumeric(string $english): bool {
    static $pat;
    if (empty($pat)) {
      $words = array_map(
        function($w) {
          return preg_quote(strtolower($w));
        },
        array_merge(array_values(self::$lowNumbers), array_values(self::$intervalsOfTen))
      );
      $pat = '/^(\-|' . implode('|', $words) . ')+$/';
    }
    return (bool) preg_match($pat, strtolower($english));
  }

}
