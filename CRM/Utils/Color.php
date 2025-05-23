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

/**
 * Static utility functions for working with colors
 */
class CRM_Utils_Color {

  const COLOR_FILE = '[civicrm.root]/bower_components/css-color-names/css-color-names.json';

  /**
   * Determine the appropriate text color for a given background.
   *
   * Based on YIQ value.
   *
   * @param string $color
   * @param string $black
   * @param string $white
   * @return string
   */
  public static function getContrast($color, $black = 'black', $white = 'white') {
    list($r, $g, $b) = self::getRgb($color);
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? $black : $white;
  }

  /**
   * Parse any color string into rgb decimal values
   *
   * Accepted formats:
   *   Full hex:     "#ffffff"
   *   Short hex:    "#fff"
   *   Color name    "white"
   *   RGB notation: "rgb(255, 255, 255)"
   *
   * @param string $color
   * @return int[]|null
   *   [red, green, blue]
   */
  public static function getRgb($color) {
    $color = str_replace(' ', '', $color);
    $color = self::nameToHex($color) ?? $color;
    if (str_starts_with($color, 'rgb(')) {
      return explode(',', substr($color, 4, strpos($color, ')') - 4));
    }
    $color = ltrim($color, '#');
    if (strlen($color) === 3) {
      $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
    }
    if (!CRM_Utils_Rule::color('#' . $color)) {
      return NULL;
    }
    return [
      hexdec(substr($color, 0, 2)),
      hexdec(substr($color, 2, 2)),
      hexdec(substr($color, 4, 2)),
    ];
  }

  /**
   * Calculate a highlight color from a base color
   *
   * @param string $color
   * @return string
   */
  public static function getHighlight($color) {
    $rgb = self::getRgb($color);
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
    return self::rgbToHex($rgb);
  }

  /**
   * Convert named color (e.g. springgreen) to hex
   *
   * @param string $colorName
   * @return string|null
   */
  public static function nameToHex($colorName) {
    if (str_contains($colorName, '#') || str_contains($colorName, '(')) {
      return NULL;
    }
    if (empty(Civi::$statics[__CLASS__]['names'])) {
      Civi::$statics[__CLASS__]['names'] = json_decode(file_get_contents(Civi::paths()->getPath(self::COLOR_FILE)), TRUE);
    }
    return Civi::$statics[__CLASS__]['names'][strtolower($colorName)] ?? NULL;
  }

  /**
   * Converts rgb array to hex string
   *
   * @param int[] $rgb
   * @return string
   */
  public static function rgbToHex($rgb) {
    $ret = '#';
    foreach ($rgb as $dec) {
      $ret .= str_pad(dechex($dec), 2, '0', STR_PAD_LEFT);
    }
    return $ret;
  }

  /**
   * Validate color input and convert it to standard hex notation
   *
   * @param string $color
   * @return bool
   */
  public static function normalize(&$color) {
    $rgb = self::getRgb($color);
    if ($rgb) {
      $color = self::rgbToHex($rgb);
      return TRUE;
    }
    return FALSE;
  }

}
