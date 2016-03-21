<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
    $val = str_replace('.', '', abs($keyValue)); // ex: -123.456 ==> 123456
    $val = substr($val, 0, $sigFigs);            // ex: 123456 => 1234

    // Move any extra digits after decimal
    $extraFigs = strlen($val) - ($sigFigs - $decFigs);
    if ($extraFigs > 0) {
      return $sign * $val / pow(10, $extraFigs); // ex: 1234 => 1.234
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
          CRM_Core_Session::setStatus(ts("Note: Please verify your configuration for Maximum File Size (in MB) <a href='%1'>Administrator >> System Settings >> Misc</a>. It should support 'upload_max_size' as defined in PHP.ini.Please check with your system administrator.", array(1 => CRM_Utils_System::url('civicrm/admin/setting/misc', 'reset=1'))), ts("Warning"), "alert");
        }
      }
      return $size;
    }
  }

}
