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
 * $Id$
 *
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
    if (isset(self::$lowNumbers[$num])) {
      return self::$lowNumbers[$num];
    }

    $tens = (int) ($num / 10);
    $last = $num % 10;
    if (isset(self::$intervalsOfTen[$tens])) {
      if ($last == 0) {
        return self::$intervalsOfTen[$tens];
      }
      else {
        return self::$intervalsOfTen[$tens] . self::$lowNumbers[$last];
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
    if (isset(self::$lowNumbers[$num])) {
      return strtolower(self::$lowNumbers[$num]);
    }

    $tens = (int) ($num / 10);
    $last = $num % 10;
    if (isset(self::$intervalsOfTen[$tens])) {
      if ($last == 0) {
        return strtolower(self::$intervalsOfTen[$tens]);
      }
      else {
        return strtolower(self::$intervalsOfTen[$tens]) . '-' . strtolower(self::$lowNumbers[$last]);
      }
    }

    if ($default === NULL) {
      throw new \RuntimeException("Cannot convert number to English: " . (int) $num);
    }
    else {
      return $default;
    }
  }

}
