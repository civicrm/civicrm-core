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
   * @param array $precision
   *   (int $significantDigits, int $postDecimalDigits).
   * @return float
   * @link https://dev.mysql.com/doc/refman/5.1/en/fixed-point-types.html
   */
  public static function createTruncatedDecimal($keyValue, $precision) {
    list ($sigFigs, $decFigs) = $precision;
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
   * Some kind of numbery-looky-printy thing.
   *
   * @param string $size
   * @param bool $checkForPostMax
   *
   * @return int
   */
  public static function formatUnitSize($size, $checkForPostMax = FALSE) {
    if ($size) {
      $last = strtolower($size{strlen($size) - 1});
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

      if ($checkForPostMax) {
        $maxImportFileSize = self::formatUnitSize(ini_get('upload_max_filesize'));
        $postMaxSize = self::formatUnitSize(ini_get('post_max_size'));
        if ($maxImportFileSize > $postMaxSize && $postMaxSize == $size) {
          CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
        }
        // respect php.ini upload_max_filesize
        if ($size > $maxImportFileSize && $size !== $postMaxSize) {
          $size = $maxImportFileSize;
          CRM_Core_Session::setStatus(ts("Note: Please verify your configuration for Maximum File Size (in MB) <a href='%1'>Administrator >> System Settings >> Misc</a>. It should support 'upload_max_size' as defined in PHP.ini.Please check with your system administrator.", [1 => CRM_Utils_System::url('civicrm/admin/setting/misc', 'reset=1')]), ts("Warning"), "alert");
        }
      }
      return $size;
    }
  }

}
