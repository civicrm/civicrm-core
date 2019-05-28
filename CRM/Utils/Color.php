<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Static utility functions for working with colors
 */
class CRM_Utils_Color {

  /**
   * Determine the appropriate text color for a given background.
   *
   * Based on YIQ value.
   *
   * @param string $hexcolor
   * @param string $black
   * @param string $white
   * @return string
   */
  public static function getContrast($hexcolor, $black = 'black', $white = 'white') {
    list($r, $g, $b) = self::getRgb($hexcolor);
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? $black : $white;
  }

  /**
   * Convert hex color to decimal
   *
   * @param string $hexcolor
   * @return array
   *   [red, green, blue]
   */
  public static function getRgb($hexcolor) {
    $hexcolor = trim($hexcolor, ' #');
    if (strlen($hexcolor) === 3) {
      $hexcolor = $hexcolor[0] . $hexcolor[0] . $hexcolor[1] . $hexcolor[1] . $hexcolor[2] . $hexcolor[2];
    }
    return [
      hexdec(substr($hexcolor, 0, 2)),
      hexdec(substr($hexcolor, 2, 2)),
      hexdec(substr($hexcolor, 4, 2)),
    ];
  }

  /**
   * Calculate a highlight color from a base color
   *
   * @param $hexcolor
   * @return string
   */
  public static function getHighlight($hexcolor) {
    $rgb = CRM_Utils_Color::getRgb($hexcolor);
    $avg = array_sum($rgb) / 3;
    foreach ($rgb as &$v) {
      if ($avg > 242) {
        // For very bright values, lower the brightness
        $v -= 50;
      }
      else {
        // Bump up brightness on a nonlinear curve - darker colors get more of a boost
        $v = min(255, intval((-.0035 * ($v - 242) ** 2) + 260));
      }
    }
    return '#' . implode(array_map('dechex', $rgb));
  }

}
