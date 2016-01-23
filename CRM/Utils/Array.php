<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Provides a collection of static methods for array manipulation.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_Array {

  /**
   * Returns $list[$key] if such element exists, or a default value otherwise.
   *
   * If $list is not actually an array at all, then the default value is
   * returned.
   *
   *
   * @param string $key
   *   Key value to look up in the array.
   * @param array $list
   *   Array from which to look up a value.
   * @param mixed $default
   *   (optional) Value to return $list[$key] does not exist.
   *
   * @return mixed
   *   Can return any type, since $list might contain anything.
   */
  public static function value($key, $list, $default = NULL) {
    if (is_array($list)) {
      return array_key_exists($key, $list) ? $list[$key] : $default;
    }
    return $default;
  }

  /**
   * Recursively searches an array for a key, returning the first value found.
   *
   * If $params[$key] does not exist and $params contains arrays, descend into
   * each array in a depth-first manner, in array iteration order.
   *
   * @param array $params
   *   The array to be searched.
   * @param string $key
   *   The key to search for.
   *
   * @return mixed
   *   The value of the key, or null if the key is not found.
   */
  public static function retrieveValueRecursive(&$params, $key) {
    if (!is_array($params)) {
      return NULL;
    }
    elseif ($value = CRM_Utils_Array::value($key, $params)) {
      return $value;
    }
    else {
      foreach ($params as $subParam) {
        if (is_array($subParam) &&
          $value = self::retrieveValueRecursive($subParam, $key)
        ) {
          return $value;
        }
      }
    }
    return NULL;
  }

  /**
   * Wraps and slightly changes the behavior of PHP's array_search().
   *
   * This function reproduces the behavior of array_search() from PHP prior to
   * version 4.2.0, which was to return NULL on failure. This function also
   * checks that $list is an array before attempting to search it.
   *
   *
   * @param mixed $value
   *   The value to search for.
   * @param array $list
   *   The array to be searched.
   *
   * @return int|string|null
   *   Returns the key, which could be an int or a string, or NULL on failure.
   */
  public static function key($value, $list) {
    if (is_array($list)) {
      $key = array_search($value, $list);

      // array_search returns key if found, false otherwise
      // it may return values like 0 or empty string which
      // evaluates to false
      // hence we must use identical comparison operator
      return ($key === FALSE) ? NULL : $key;
    }
    return NULL;
  }

  /**
   * Builds an XML fragment representing an array.
   *
   * Depending on the nature of the keys of the array (and its sub-arrays,
   * if any) the XML fragment may not be valid.
   *
   * @param array $list
   *   The array to be serialized.
   * @param int $depth
   *   (optional) Indentation depth counter.
   * @param string $seperator
   *   (optional) String to be appended after open/close tags.
   *
   * @return string
   *   XML fragment representing $list.
   */
  public static function &xml(&$list, $depth = 1, $seperator = "\n") {
    $xml = '';
    foreach ($list as $name => $value) {
      $xml .= str_repeat(' ', $depth * 4);
      if (is_array($value)) {
        $xml .= "<{$name}>{$seperator}";
        $xml .= self::xml($value, $depth + 1, $seperator);
        $xml .= str_repeat(' ', $depth * 4);
        $xml .= "</{$name}>{$seperator}";
      }
      else {
        // make sure we escape value
        $value = self::escapeXML($value);
        $xml .= "<{$name}>$value</{$name}>{$seperator}";
      }
    }
    return $xml;
  }

  /**
   * Sanitizes a string for serialization in CRM_Utils_Array::xml().
   *
   * Replaces '&', '<', and '>' with their XML escape sequences. Replaces '^A'
   * with a comma.
   *
   * @param string $value
   *   String to be sanitized.
   *
   * @return string
   *   Sanitized version of $value.
   */
  public static function escapeXML($value) {
    static $src = NULL;
    static $dst = NULL;

    if (!$src) {
      $src = array('&', '<', '>', '');
      $dst = array('&amp;', '&lt;', '&gt;', ',');
    }

    return str_replace($src, $dst, $value);
  }

  /**
   * Converts a nested array to a flat array.
   *
   * The nested structure is preserved in the string values of the keys of the
   * flat array.
   *
   * Example nested array:
   * Array
   * (
   *     [foo] => Array
   *         (
   *             [0] => bar
   *             [1] => baz
   *             [2] => 42
   *         )
   *
   *     [asdf] => Array
   *         (
   *             [merp] => bleep
   *             [quack] => Array
   *                 (
   *                     [0] => 1
   *                     [1] => 2
   *                     [2] => 3
   *                 )
   *
   *         )
   *
   *     [quux] => 999
   * )
   *
   * Corresponding flattened array:
   * Array
   * (
   *     [foo.0] => bar
   *     [foo.1] => baz
   *     [foo.2] => 42
   *     [asdf.merp] => bleep
   *     [asdf.quack.0] => 1
   *     [asdf.quack.1] => 2
   *     [asdf.quack.2] => 3
   *     [quux] => 999
   * )
   *
   * @param array $list
   *   Array to be flattened.
   * @param array $flat
   *   Destination array.
   * @param string $prefix
   *   (optional) String to prepend to keys.
   * @param string $seperator
   *   (optional) String that separates the concatenated keys.
   */
  public static function flatten(&$list, &$flat, $prefix = '', $seperator = ".") {
    foreach ($list as $name => $value) {
      $newPrefix = ($prefix) ? $prefix . $seperator . $name : $name;
      if (is_array($value)) {
        self::flatten($value, $flat, $newPrefix, $seperator);
      }
      else {
        if (!empty($value)) {
          $flat[$newPrefix] = $value;
        }
      }
    }
  }

  /**
   * Converts an array with path-like keys into a tree of arrays.
   *
   * This function is the inverse of CRM_Utils_Array::flatten().
   *
   * @param string $delim
   *   A path delimiter
   * @param array $arr
   *   A one-dimensional array indexed by string keys
   *
   * @return array
   *   Array-encoded tree
   */
  public function unflatten($delim, &$arr) {
    $result = array();
    foreach ($arr as $key => $value) {
      $path = explode($delim, $key);
      $node = &$result;
      while (count($path) > 1) {
        $key = array_shift($path);
        if (!isset($node[$key])) {
          $node[$key] = array();
        }
        $node = &$node[$key];
      }
      // last part of path
      $key = array_shift($path);
      $node[$key] = $value;
    }
    return $result;
  }

  /**
   * Merges two arrays.
   *
   * If $a1[foo] and $a2[foo] both exist and are both arrays, the merge
   * process recurses into those sub-arrays. If $a1[foo] and $a2[foo] both
   * exist but they are not both arrays, the value from $a1 overrides the
   * value from $a2 and the value from $a2 is discarded.
   *
   * @param array $a1
   *   First array to be merged.
   * @param array $a2
   *   Second array to be merged.
   *
   * @return array
   *   The merged array.
   */
  public static function crmArrayMerge($a1, $a2) {
    if (empty($a1)) {
      return $a2;
    }

    if (empty($a2)) {
      return $a1;
    }

    $a3 = array();
    foreach ($a1 as $key => $value) {
      if (array_key_exists($key, $a2) &&
        is_array($a2[$key]) && is_array($a1[$key])
      ) {
        $a3[$key] = array_merge($a1[$key], $a2[$key]);
      }
      else {
        $a3[$key] = $a1[$key];
      }
    }

    foreach ($a2 as $key => $value) {
      if (array_key_exists($key, $a1)) {
        // already handled in above loop
        continue;
      }
      $a3[$key] = $a2[$key];
    }

    return $a3;
  }

  /**
   * Determines whether an array contains any sub-arrays.
   *
   * @param array $list
   *   The array to inspect.
   *
   * @return bool
   *   True if $list contains at least one sub-array, false otherwise.
   */
  public static function isHierarchical(&$list) {
    foreach ($list as $n => $v) {
      if (is_array($v)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Is array A a subset of array B.
   *
   * @param array $subset
   * @param array $superset
   *
   * @return bool
   *   TRUE if $subset is a subset of $superset
   */
  public static function isSubset($subset, $superset) {
    foreach ($subset as $expected) {
      if (!in_array($expected, $superset)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Searches an array recursively in an optionally case-insensitive manner.
   *
   * @param string $value
   *   Value to search for.
   * @param array $params
   *   Array to search within.
   * @param bool $caseInsensitive
   *   (optional) Whether to search in a case-insensitive manner.
   *
   * @return bool
   *   True if $value was found, false otherwise.
   */
  public static function crmInArray($value, $params, $caseInsensitive = TRUE) {
    foreach ($params as $item) {
      if (is_array($item)) {
        $ret = crmInArray($value, $item, $caseInsensitive);
      }
      else {
        $ret = ($caseInsensitive) ? strtolower($item) == strtolower($value) : $item == $value;
        if ($ret) {
          return $ret;
        }
      }
    }
    return FALSE;
  }

  /**
   * Convert associative array names to values and vice-versa.
   *
   * This function is used by both the web form layer and the api. Note that
   * the api needs the name => value conversion, also the view layer typically
   * requires value => name conversion
   *
   * @param array $defaults
   * @param string $property
   * @param $lookup
   * @param $reverse
   *
   * @return bool
   */
  public static function lookupValue(&$defaults, $property, $lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!array_key_exists(strtolower($src), array_change_key_case($defaults, CASE_LOWER))) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    // trim lookup array, ignore . ( fix for CRM-1514 ), eg for prefix/suffix make sure Dr. and Dr both are valid
    $newLook = array();
    foreach ($look as $k => $v) {
      $newLook[trim($k, ".")] = $v;
    }

    $look = $newLook;

    if (is_array($look)) {
      if (!array_key_exists(trim(strtolower($defaults[strtolower($src)]), '.'), array_change_key_case($look, CASE_LOWER))) {
        return FALSE;
      }
    }

    $tempLook = array_change_key_case($look, CASE_LOWER);

    $defaults[$dst] = $tempLook[trim(strtolower($defaults[strtolower($src)]), '.')];
    return TRUE;
  }

  /**
   * Checks whether an array is empty.
   *
   * An array is empty if its values consist only of NULL and empty sub-arrays.
   * Containing a non-NULL value or non-empty array makes an array non-empty.
   *
   * If something other than an array is passed, it is considered to be empty.
   *
   * If nothing is passed at all, the default value provided is empty.
   *
   * @param array $array
   *   (optional) Array to be checked for emptiness.
   *
   * @return bool
   *   True if the array is empty.
   */
  public static function crmIsEmptyArray($array = array()) {
    if (!is_array($array)) {
      return TRUE;
    }
    foreach ($array as $element) {
      if (is_array($element)) {
        if (!self::crmIsEmptyArray($element)) {
          return FALSE;
        }
      }
      elseif (isset($element)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Sorts an associative array of arrays by an attribute using strnatcmp().
   *
   * @param array $array
   *   Array to be sorted.
   * @param string $field
   *   Name of the attribute used for sorting.
   *
   * @return array
   *   Sorted array
   */
  public static function crmArraySortByField($array, $field) {
    $code = "return strnatcmp(\$a['$field'], \$b['$field']);";
    uasort($array, create_function('$a,$b', $code));
    return $array;
  }

  /**
   * Recursively removes duplicate values from a multi-dimensional array.
   *
   * @param array $array
   *   The input array possibly containing duplicate values.
   *
   * @return array
   *   The input array with duplicate values removed.
   */
  public static function crmArrayUnique($array) {
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));
    foreach ($result as $key => $value) {
      if (is_array($value)) {
        $result[$key] = self::crmArrayUnique($value);
      }
    }
    return $result;
  }

  /**
   * Sorts an array and maintains index association (with localization).
   *
   * Uses Collate from the PECL "intl" package, if available, for UTF-8
   * sorting (e.g. list of countries). Otherwise calls PHP's asort().
   *
   * On Debian/Ubuntu: apt-get install php5-intl
   *
   * @param array $array
   *   (optional) Array to be sorted.
   *
   * @return array
   *   Sorted array.
   */
  public static function asort($array = array()) {
    $lcMessages = CRM_Utils_System::getUFLocale();

    if ($lcMessages && $lcMessages != 'en_US' && class_exists('Collator')) {
      $collator = new Collator($lcMessages . '.utf8');
      $collator->asort($array);
    }
    else {
      // This calls PHP's built-in asort().
      asort($array);
    }

    return $array;
  }

  /**
   * Unsets an arbitrary list of array elements from an associative array.
   *
   * @param array $items
   *   The array from which to remove items.
   *
   * Additional params:
   *   When passed a string, unsets $items[$key].
   *   When passed an array of strings, unsets $items[$k] for each string $k in the array.
   */
  public static function remove(&$items) {
    foreach (func_get_args() as $n => $key) {
      // Skip argument 0 ($items) by testing $n for truth.
      if ($n && is_array($key)) {
        foreach ($key as $k) {
          unset($items[$k]);
        }
      }
      elseif ($n) {
        unset($items[$key]);
      }
    }
  }

  /**
   * Builds an array-tree which indexes the records in an array.
   *
   * @param string[] $keys
   *   Properties by which to index.
   * @param object|array $records
   *
   * @return array
   *   Multi-dimensional array, with one layer for each key.
   */
  public static function index($keys, $records) {
    $final_key = array_pop($keys);

    $result = array();
    foreach ($records as $record) {
      $node = &$result;
      foreach ($keys as $key) {
        if (is_array($record)) {
          $keyvalue = isset($record[$key]) ? $record[$key] : NULL;
        }
        else {
          $keyvalue = isset($record->{$key}) ? $record->{$key} : NULL;
        }
        if (isset($node[$keyvalue]) && !is_array($node[$keyvalue])) {
          $node[$keyvalue] = array();
        }
        $node = &$node[$keyvalue];
      }
      if (is_array($record)) {
        $node[$record[$final_key]] = $record;
      }
      else {
        $node[$record->{$final_key}] = $record;
      }
    }
    return $result;
  }

  /**
   * Iterates over a list of records and returns the value of some property.
   *
   * @param string $prop
   *   Property to retrieve.
   * @param array|object $records
   *   A list of records.
   *
   * @return array
   *   Keys are the original keys of $records; values are the $prop values.
   */
  public static function collect($prop, $records) {
    $result = array();
    if (is_array($records)) {
      foreach ($records as $key => $record) {
        if (is_object($record)) {
          $result[$key] = $record->{$prop};
        }
        else {
          $result[$key] = $record[$prop];
        }
      }
    }
    return $result;
  }

  /**
   * Iterates over a list of objects and executes some method on each.
   *
   * Comparison:
   *   - This is like array_map(), except it executes the objects' method
   *     instead of a free-form callable.
   *   - This is like Array::collect(), except it uses a method
   *     instead of a property.
   *
   * @param string $method
   *   The method to execute.
   * @param array|Traversable $objects
   *   A list of objects.
   * @param array $args
   *   An optional list of arguments to pass to the method.
   *
   * @return array
   *   Keys are the original keys of $objects; values are the method results.
   */
  public static function collectMethod($method, $objects, $args = array()) {
    $result = array();
    if (is_array($objects)) {
      foreach ($objects as $key => $object) {
        $result[$key] = call_user_func_array(array($object, $method), $args);
      }
    }
    return $result;
  }

  /**
   * Trims delimiters from a string and then splits it using explode().
   *
   * This method works mostly like PHP's built-in explode(), except that
   * surrounding delimiters are trimmed before explode() is called.
   *
   * Also, if an array or NULL is passed as the $values parameter, the value is
   * returned unmodified rather than being passed to explode().
   *
   * @param array|null|string $values
   *   The input string (or an array, or NULL).
   * @param string $delim
   *   (optional) The boundary string.
   *
   * @return array|null
   *   An array of strings produced by explode(), or the unmodified input
   *   array, or NULL.
   */
  public static function explodePadded($values, $delim = CRM_Core_DAO::VALUE_SEPARATOR) {
    if ($values === NULL) {
      return NULL;
    }
    // If we already have an array, no need to continue
    if (is_array($values)) {
      return $values;
    }
    // Empty string -> empty array
    if ($values === '') {
      return array();
    }
    return explode($delim, trim((string) $values, $delim));
  }

  /**
   * Joins array elements with a string, adding surrounding delimiters.
   *
   * This method works mostly like PHP's built-in implode(), but the generated
   * string is surrounded by delimiter characters. Also, if NULL is passed as
   * the $values parameter, NULL is returned.
   *
   * @param mixed $values
   *   Array to be imploded. If a non-array is passed, it will be cast to an
   *   array.
   * @param string $delim
   *   Delimiter to be used for implode() and which will surround the output
   *   string.
   *
   * @return string|NULL
   *   The generated string, or NULL if NULL was passed as $values parameter.
   */
  public static function implodePadded($values, $delim = CRM_Core_DAO::VALUE_SEPARATOR) {
    if ($values === NULL) {
      return NULL;
    }
    // If we already have a string, strip $delim off the ends so it doesn't get added twice
    if (is_string($values)) {
      $values = trim($values, $delim);
    }
    return $delim . implode($delim, (array) $values) . $delim;
  }

  /**
   * Modifies a key in an array while preserving the key order.
   *
   * By default when an element is added to an array, it is added to the end.
   * This method allows for changing an existing key while preserving its
   * position in the array.
   *
   * The array is both modified in-place and returned.
   *
   * @param array $elementArray
   *   Array to manipulate.
   * @param string $oldKey
   *   Old key to be replaced.
   * @param string $newKey
   *   Replacement key string.
   *
   * @throws Exception
   *   Throws a generic Exception if $oldKey is not found in $elementArray.
   *
   * @return array
   *   The manipulated array.
   */
  public static function crmReplaceKey(&$elementArray, $oldKey, $newKey) {
    $keys = array_keys($elementArray);
    if (FALSE === $index = array_search($oldKey, $keys)) {
      throw new Exception(sprintf('key "%s" does not exit', $oldKey));
    }
    $keys[$index] = $newKey;
    $elementArray = array_combine($keys, array_values($elementArray));
    return $elementArray;
  }

  /**
   * Searches array keys by regex, returning the value of the first match.
   *
   * Given a regular expression and an array, this method searches the keys
   * of the array using the regular expression. The first match is then used
   * to index into the array, and the associated value is retrieved and
   * returned. If no matches are found, or if something other than an array
   * is passed, then a default value is returned. Unless otherwise specified,
   * the default value is NULL.
   *
   * @param string $regexKey
   *   The regular expression to use when searching for matching keys.
   * @param array $list
   *   The array whose keys will be searched.
   * @param mixed $default
   *   (optional) The default value to return if the regex does not match an
   *   array key, or if something other than an array is passed.
   *
   * @return mixed
   *   The value found.
   */
  public static function valueByRegexKey($regexKey, $list, $default = NULL) {
    if (is_array($list) && $regexKey) {
      $matches = preg_grep($regexKey, array_keys($list));
      $key = reset($matches);
      return ($key && array_key_exists($key, $list)) ? $list[$key] : $default;
    }
    return $default;
  }

  /**
   * Generates the Cartesian product of zero or more vectors.
   *
   * @param array $dimensions
   *   List of dimensions to multiply.
   *   Each key is a dimension name; each value is a vector.
   * @param array $template
   *   (optional) A base set of values included in every output.
   *
   * @return array
   *   Each item is a distinct combination of values from $dimensions.
   *
   *   For example, the product of
   *   {
   *   fg => {red, blue},
   *   bg => {white, black}
   *   }
   *   would be
   *   {
   *   {fg => red, bg => white},
   *   {fg => red, bg => black},
   *   {fg => blue, bg => white},
   *   {fg => blue, bg => black}
   *   }
   */
  public static function product($dimensions, $template = array()) {
    if (empty($dimensions)) {
      return array($template);
    }

    foreach ($dimensions as $key => $value) {
      $firstKey = $key;
      $firstValues = $value;
      break;
    }
    unset($dimensions[$key]);

    $results = array();
    foreach ($firstValues as $firstValue) {
      foreach (self::product($dimensions, $template) as $result) {
        $result[$firstKey] = $firstValue;
        $results[] = $result;
      }
    }

    return $results;
  }

  /**
   * Get the first element of an array.
   *
   * @param array $array
   * @return mixed|NULL
   */
  public static function first($array) {
    foreach ($array as $value) {
      return $value;
    }
    return NULL;
  }

  /**
   * Extract any $keys from $array and copy to a new array.
   *
   * Note: If a $key does not appear in $array, then it will
   * not appear in the result.
   *
   * @param array $array
   * @param array $keys
   *   List of keys to copy.
   * @return array
   */
  public static function subset($array, $keys) {
    $result = array();
    foreach ($keys as $key) {
      if (isset($array[$key])) {
        $result[$key] = $array[$key];
      }
    }
    return $result;
  }

  /**
   * Transform an associative array of key=>value pairs into a non-associative array of arrays.
   * This is necessary to preserve sort order when sending an array through json_encode.
   *
   * @param array $associative
   * @param string $keyName
   * @param string $valueName
   * @return array
   */
  public static function makeNonAssociative($associative, $keyName = 'key', $valueName = 'value') {
    $output = array();
    foreach ($associative as $key => $val) {
      $output[] = array($keyName => $key, $valueName => $val);
    }
    return $output;
  }

  /**
   * Diff multidimensional arrays
   * (array_diff does not support multidimensional array)
   *
   * @param array $array1
   * @param array $array2
   * @return array
   */
  public static function multiArrayDiff($array1, $array2) {
    $arrayDiff = array();
    foreach ($array1 as $mKey => $mValue) {
      if (array_key_exists($mKey, $array2)) {
        if (is_array($mValue)) {
          $recursiveDiff = self::multiArrayDiff($mValue, $array2[$mKey]);
          if (count($recursiveDiff)) {
            $arrayDiff[$mKey] = $recursiveDiff;
          }
        }
        else {
          if ($mValue != $array2[$mKey]) {
            $arrayDiff[$mKey] = $mValue;
          }
        }
      }
      else {
        $arrayDiff[$mKey] = $mValue;
      }
    }
    return $arrayDiff;
  }

  /**
   * Given a 2-dimensional matrix, create a new matrix with a restricted list of columns.
   *
   * @param array $matrix
   *   All matrix data, as a list of rows.
   * @param array $columns
   *   List of column names.
   * @return array
   */
  public static function filterColumns($matrix, $columns) {
    $newRows = array();
    foreach ($matrix as $pos => $oldRow) {
      $newRow = array();
      foreach ($columns as $column) {
        $newRow[$column] = CRM_Utils_Array::value($column, $oldRow);
      }
      $newRows[$pos] = $newRow;
    }
    return $newRows;
  }

  /**
   * Rewrite the keys in an array by filtering through a function.
   *
   * @param array $array
   * @param callable $func
   *   Function($key, $value). Returns the new key.
   * @return array
   */
  public static function rekey($array, $func) {
    $result = array();
    foreach ($array as $key => $value) {
      $newKey = $func($key, $value);
      $result[$newKey] = $value;
    }
    return $result;
  }

  /**
   * Copy all properties of $other into $array (recursively).
   *
   * @param array|ArrayAccess $array
   * @param array $other
   */
  public static function extend(&$array, $other) {
    foreach ($other as $key => $value) {
      if (is_array($value)) {
        self::extend($array[$key], $value);
      }
      else {
        $array[$key] = $value;
      }
    }
  }

  /**
   * Get a single value from an array-tre.
   *
   * @param array $arr
   *   Ex: array('foo'=>array('bar'=>123)).
   * @param array $pathParts
   *   Ex: array('foo',bar').
   * @return mixed|NULL
   *   Ex 123.
   */
  public static function pathGet($arr, $pathParts) {
    $r = $arr;
    foreach ($pathParts as $part) {
      if (!isset($r[$part])) {
        return NULL;
      }
      $r = $r[$part];
    }
    return $r;
  }

  /**
   * Set a single value in an array tree.
   *
   * @param array $arr
   *   Ex: array('foo'=>array('bar'=>123)).
   * @param array $pathParts
   *   Ex: array('foo',bar').
   * @param $value
   *   Ex: 456.
   */
  public static function pathSet(&$arr, $pathParts, $value) {
    $r = &$arr;
    $last = array_pop($pathParts);
    foreach ($pathParts as $part) {
      if (!isset($r[$part])) {
        $r[$part] = array();
      }
      $r = &$r[$part];
    }
    $r[$last] = $value;
  }

  /**
   * Convert a simple dictionary into separate key+value records.
   *
   * @param array $array
   *   Ex: array('foo' => 'bar').
   * @param string $keyField
   *   Ex: 'key'.
   * @param string $valueField
   *   Ex: 'value'.
   * @return array
   *   Ex: array(
   *     0 => array('key' => 'foo', 'value' => 'bar')
   *   ).
   */
  public static function toKeyValueRows($array, $keyField = 'key', $valueField = 'value') {
    $result = array();
    foreach ($array as $key => $value) {
      $result[] = array(
        $keyField => $key,
        $valueField => $value,
      );
    }
    return $result;
  }

  /**
   * Convert array where key(s) holds the actual value and value(s) as 1 into array of actual values
   *  Ex: array('foobar' => 1, 4 => 1) formatted into array('foobar', 4)
   *
   * @param array $array
   * @return void
   */
  public static function formatArrayKeys(&$array) {
    $keys = array_keys($array, 1);
    if (count($keys) > 1 ||
      (count($keys) == 1 &&
        (current($keys) > 1 ||
          is_string(current($keys)) ||
          (current($keys) == 1 && $array[1] == 1) // handle (0 => 4), (1 => 1)
        )
      )
    ) {
      $array = $keys;
    }
  }

}
