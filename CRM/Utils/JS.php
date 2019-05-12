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
 * Parse Javascript content and extract translatable strings.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Utils_JS {

  /**
   * Parse a javascript file for translatable strings.
   *
   * @param string $jsCode
   *   Raw Javascript code.
   * @return array
   *   Array of translatable strings
   */
  public static function parseStrings($jsCode) {
    $strings = [];
    // Match all calls to ts() in an array.
    // Note: \s also matches newlines with the 's' modifier.
    preg_match_all('~
      [^\w]ts\s*                                    # match "ts" with whitespace
      \(\s*                                         # match "(" argument list start
      ((?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+)\s*
      [,\)]                                         # match ")" or "," to finish
      ~sx', $jsCode, $matches);
    foreach ($matches[1] as $text) {
      $quote = $text[0];
      // Remove newlines
      $text = str_replace("\\\n", '', $text);
      // Unescape escaped quotes
      $text = str_replace('\\' . $quote, $quote, $text);
      // Remove end quotes
      $text = substr(ltrim($text, $quote), 0, -1);
      $strings[$text] = $text;
    }
    return array_values($strings);
  }

  /**
   * Identify duplicate, adjacent, identical closures and consolidate them.
   *
   * Note that you can only dedupe closures if they are directly adjacent and
   * have exactly the same parameters.
   *
   * @param array $scripts
   *   Javascript source.
   * @param array $localVars
   *   Ordered list of JS vars to identify the start of a closure.
   * @param array $inputVals
   *   Ordered list of input values passed into the closure.
   * @return string
   *   Javascript source.
   */
  public static function dedupeClosures($scripts, $localVars, $inputVals) {
    // Example opening: (function (angular, $, _) {
    $opening = '\s*\(\s*function\s*\(\s*';
    $opening .= implode(',\s*', array_map(function ($v) {
      return preg_quote($v, '/');
    }, $localVars));
    $opening .= '\)\s*\{';
    $opening = '/^' . $opening . '/';

    // Example closing: })(angular, CRM.$, CRM._);
    $closing = '\}\s*\)\s*\(\s*';
    $closing .= implode(',\s*', array_map(function ($v) {
      return preg_quote($v, '/');
    }, $inputVals));
    $closing .= '\);\s*';
    $closing = "/$closing\$/";

    $scripts = array_values($scripts);
    for ($i = count($scripts) - 1; $i > 0; $i--) {
      if (preg_match($closing, $scripts[$i - 1]) && preg_match($opening, $scripts[$i])) {
        $scripts[$i - 1] = preg_replace($closing, '', $scripts[$i - 1]);
        $scripts[$i] = preg_replace($opening, '', $scripts[$i]);
      }
    }

    return $scripts;
  }

  /**
   * This is a primitive comment stripper. It doesn't catch all comments
   * and falls short of minification, but it doesn't munge Angular injections
   * and is fast enough to run synchronously (without caching).
   *
   * At time of writing, running this against the Angular modules, this impl
   * of stripComments currently adds 10-20ms and cuts ~7%.
   *
   * Please be extremely cautious about extending this. If you want better
   * minification, you should probably remove this implementation,
   * import a proper JSMin implementation, and cache its output.
   *
   * @param string $script
   * @return string
   */
  public static function stripComments($script) {
    return preg_replace(":^\\s*//[^\n]+$:m", "", $script);
  }

}
