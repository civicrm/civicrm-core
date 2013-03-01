<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Utils_JS {
  /**
   * Parse a javascript file for translatable strings
   *
   * @param string $jsCode raw Javascript code
   * @return array of translatable strings
   */
  public static function parseStrings($jsCode) {
    $strings = array();
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
}