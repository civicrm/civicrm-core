<?php
declare(strict_types = 1);

namespace Civi\Util;

final class PhpSpreadsheetUtil {

  /**
   * Converts a format string that is compatible with
   * \CRM_Utils_Date::customFormat() to a format code for PhpSpreadsheet.
   *
   * @see \CRM_Utils_Date::customFormat()
   * @see https://phpspreadsheet.readthedocs.io/en/latest/topics/The%20Dating%20Game/#formatting-options
   */
  public static function crmDateFormatToFormatCode(string $format): string {
    // Drop strings like " Uhr" or " o'clock".
    $format = preg_replace('/( (?!%)[^%\s]+)/', '', $format);
    // Drop everything after a format character that is neither a punctuation character nor whitespace.
    $format = preg_replace('/(%[a-zA-Z])[^\p{P}\s]+/', '$1', $format);
    // Drop everything after a punctuation character ("%" excluded) that is not "%" or whitespace.
    $format = preg_replace('/(?!%)([\p{P}])[^%\s]+/', '$1', $format);

    return trim(strtr($format, [
      '%A' => 'dddd',
      '%a' => 'ddd',
      '%b' => 'mmm',
      '%B' => 'mmmm',
      '%d' => 'dd',
      '%e' => ' d',
      '%E' => 'd',
      // Number suffixes like "st" or "nd" are not possible.
      '%f' => '',
      '%H' => 'hh',
      '%h' => 'hh',
      '%I' => 'hh',
      '%k' => ' h',
      '%l' => ' h',
      '%m' => 'mm',
      '%M' => 'mm',
      '%i' => 'mm',
      '%p' => 'AM/PM',
      '%P' => 'AM/PM',
      '%Y' => 'yyyy',
      '%y' => 'yy',
      '%s' => 'ss',
      '%S' => 'ss',
      // Timezone is not possible.
      '%Z' => '',
    ]));
  }

}
