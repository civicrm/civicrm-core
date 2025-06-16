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
 * Parse Javascript content and extract translatable strings.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_JS {

  /**
   * Parse a javascript file for translatable strings using ts().
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
   * Get Afform content selectors
   */
  public static function getContentSelectors() {
    return ['.af-title', 'p.af-text', 'div.af-markup', 'button'];
  }

  /**
   * Get Afform Attribute selectors
   */
  public static function getAttributeSelectors() {
    return ['af-title', 'af-copy', 'af-repeat', 'title'];
  }

  /**
   * Get Afform Sub-attribute selectors
   */
  public static function getDefnSelectors() {
    return ['label', 'help_pre', 'help_post', 'input_attrs.placeholder', 'options.*.label'];
  }

  /**
   * Identify duplicate, adjacent, identical closures and consolidate them.
   *
   * Note that you can only dedupe closures if they are directly adjacent and
   * have exactly the same parameters.
   *
   * Also dedupes the "use strict" directive as it is only meaningful at the beginning of a closure.
   *
   * @param array $scripts
   *   Javascript source.
   * @param array $localVars
   *   Ordered list of JS vars to identify the start of a closure.
   * @param array $inputVals
   *   Ordered list of input values passed into the closure.
   * @return string[]
   *   Javascript source.
   */
  public static function dedupeClosures($scripts, $localVars, $inputVals) {
    // Example opening: (function (angular, $, _) {
    $opening = '\s*\(\s*function\s*\(\s*';
    $opening .= implode(',\s*', array_map(function ($v) {
      return preg_quote($v, '/');
    }, $localVars));
    $opening .= '\)\s*\{';
    $opening = '/^' . $opening . '\s*(?:"use strict";\s|\'use strict\';\s)?/';

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
    // This function is a little naive, and some expressions may trip it up. Opt-out if anything smells fishy.
    if (preg_match(';`\r?\n//;', $script)) {
      return $script;
    }
    return preg_replace("#^\\s*//[^\n]*$(?:\r\n|\n)?#m", "", $script);
  }

  /**
   * Decodes a js variable (not necessarily strict json but valid js) into a php variable.
   *
   * This is similar to using json_decode($js, TRUE) but more forgiving about syntax.
   *
   * ex. {a: 'Apple', 'b': "Banana", c: [1, 2, 3]}
   * Returns: [
   *   'a' => 'Apple',
   *   'b' => 'Banana',
   *   'c' => [1, 2, 3],
   * ]
   *
   * @param string $js
   * @param bool $throwException
   * @return mixed
   * @throws CRM_Core_Exception
   */
  public static function decode($js, $throwException = FALSE) {
    $js = trim($js);
    $first = substr($js, 0, 1);
    $last = substr($js, -1);
    if ($first === "'" && $last === "'") {
      $js = self::convertSingleQuoteString($js, $throwException);
    }
    elseif (($first === '{' && $last === '}') || ($first === '[' && $last === ']')) {
      $obj = self::getRawProps($js);
      foreach ($obj as $idx => $item) {
        $obj[$idx] = self::decode($item, $throwException);
      }
      return $obj;
    }
    $result = json_decode($js ?? '');
    if ($throwException && $result === NULL && $js !== 'null') {
      throw new CRM_Core_Exception(json_last_error_msg());
    }
    return $result;
  }

  /**
   * @param string $str
   * @param bool $throwException
   * @return string|null
   * @throws CRM_Core_Exception
   */
  public static function convertSingleQuoteString(string $str, $throwException) {
    // json_decode can only handle double quotes around strings, so convert single-quoted strings
    $backslash = chr(0) . 'backslash' . chr(0);
    $str = str_replace(['\\\\', '\\"', '"', '\\&', '\\/'], [$backslash, '"', '\\"', '&', '/'], substr($str, 1, -1));
    // Ensure the string doesn't terminate early by checking that all single quotes are escaped
    if (preg_match("/[^\\\\]'/", $str)) {
      if ($throwException) {
        throw new CRM_Core_Exception('Invalid string passed to CRM_Utils_JS::decode');
      }
      return NULL;
    }
    return '"' . str_replace(["\\'", $backslash], ["'", '\\\\'], $str) . '"';
  }

  /**
   * Encodes a variable to js notation (not strict json) suitable for e.g. an angular attribute.
   *
   * Like json_encode() but the output looks more like native javascript,
   * with single quotes around strings and no unnecessary quotes around object keys.
   *
   * Ex input: [
   *   'a' => 'Apple',
   *   'b' => 'Banana',
   *   'c' => [1, 2, 3],
   * ]
   * Ex output: {a: 'Apple', b: 'Banana', c: [1, 2, 3]}
   *
   * @param mixed $value
   * @return string
   */
  public static function encode($value) {
    if (is_array($value)) {
      return self::writeObject($value, TRUE);
    }
    $result = json_encode($value, JSON_UNESCAPED_SLASHES);
    // Convert double-quotes around string to single quotes
    if (is_string($value) && substr($result, 0, 1) === '"' && substr($result, -1) === '"') {
      $backslash = chr(0) . 'backslash' . chr(0);
      return "'" . str_replace(['\\\\', '\\"', "'", $backslash], [$backslash, '"', "\\'", '\\\\'], substr($result, 1, -1)) . "'";
    }
    return $result;
  }

  /**
   * Gets the properties of a javascript object/array WITHOUT decoding them.
   *
   * Useful when the object might contain js functions, expressions, etc. which cannot be decoded.
   * Returns an array with keys as property names and values as raw strings of js.
   *
   * Ex Input: {foo: getFoo(arg), 'bar': function() {return "bar";}}
   * Returns: [
   *   'foo' => 'getFoo(arg)',
   *   'bar' => 'function() {return "bar";}',
   * ]
   *
   * @param string $js
   * @return array
   * @throws Exception
   */
  public static function getRawProps($js) {
    $js = trim($js);
    if (!is_string($js) || $js === '' || !($js[0] === '{' || $js[0] === '[')) {
      throw new Exception("Invalid js object string passed to CRM_Utils_JS::getRawProps");
    }
    $chars = str_split(substr($js, 1));
    $isEscaped = $quote = NULL;
    $type = $js[0] === '{' ? 'object' : 'array';
    $key = $type == 'array' ? 0 : NULL;
    $item = '';
    $end = strlen($js) - 2;
    $quotes = ['"', "'", '/'];
    $brackets = [
      '}' => '{',
      ')' => '(',
      ']' => '[',
      ':' => '?',
    ];
    $enclosures = array_fill_keys($brackets, 0);
    $result = [];
    foreach ($chars as $index => $char) {
      if (!$isEscaped && in_array($char, $quotes, TRUE)) {
        // Open quotes, taking care not to mistake the division symbol for opening a regex
        if (!$quote && !($char == '/' && preg_match('{[\w)]\s*$}', $item))) {
          $quote = $char;
        }
        // Close quotes
        elseif ($char === $quote) {
          $quote = NULL;
        }
      }
      if (!$quote) {
        // Delineates property key
        if ($char == ':' && !array_filter($enclosures) && !$key) {
          $key = $item;
          $item = '';
          continue;
        }
        // Delineates property value
        if (($char == ',' || $index == $end) && !array_filter($enclosures) && isset($key) && trim($item) !== '') {
          // Trim, unquote, and unescape characters in key
          if ($type == 'object') {
            $key = trim($key);
            $key = in_array($key[0], $quotes) ? self::decode($key) : $key;
          }
          $result[$key] = trim($item);
          $key = $type == 'array' ? $key + 1 : NULL;
          $item = '';
          continue;
        }
        // Open brackets - we'll ignore delineators inside
        if (isset($enclosures[$char])) {
          $enclosures[$char]++;
        }
        // Close brackets
        if (isset($brackets[$char]) && $enclosures[$brackets[$char]]) {
          $enclosures[$brackets[$char]]--;
        }
      }
      $item .= $char;
      // We are escaping the next char if this is a backslash not preceded by an odd number of backslashes
      $isEscaped = $char === '\\' && ((strlen($item) - strlen(rtrim($item, '\\'))) % 2);
    }
    return $result;
  }

  /**
   * Converts a php array to javascript object/array notation (not strict JSON).
   *
   * Does not encode keys unless they contain special characters.
   * Does not encode values by default, so either specify $encodeValues = TRUE,
   * or pass strings of valid js/json as values (per output from getRawProps).
   * @see CRM_Utils_JS::getRawProps
   *
   * @param array $obj
   * @param bool $encodeValues
   * @return string
   */
  public static function writeObject($obj, $encodeValues = FALSE) {
    $js = [];
    $brackets = isset($obj[0]) && array_keys($obj) === range(0, count($obj) - 1) ? ['[', ']'] : ['{', '}'];
    foreach ($obj as $key => $val) {
      if ($encodeValues) {
        $val = self::encode($val);
      }
      if ($brackets[0] == '{') {
        // Enclose the key in quotes unless it is purely alphanumeric
        if (preg_match('/\W/', $key)) {
          // Prefer single quotes around keys
          $key = self::encode($key);
        }
        $js[] = "$key: $val";
      }
      else {
        $js[] = $val;
      }
    }
    return $brackets[0] . implode(', ', $js) . $brackets[1];
  }

}
