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
 * Provides a collection of static methods for array manipulation.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Array {

  /**
   * Cast a value to an array.
   *
   * This is similar to PHP's `(array)`, but it also converts iterators.
   *
   * @param mixed $value
   * @return array
   */
  public static function cast($value) {
    if (is_array($value)) {
      return $value;
    }
    if ($value instanceof CRM_Utils_LazyArray || $value instanceof ArrayObject) {
      // iterator_to_array() would work here, but getArrayCopy() doesn't require actual iterations.
      return $value->getArrayCopy();
    }
    if (is_iterable($value)) {
      return iterator_to_array($value);
    }
    if (is_scalar($value)) {
      return [$value];
    }
    throw new \RuntimeException(sprintf("Cannot cast %s to array", gettype($value)));
  }

  /**
   * Returns $list[$key] if such element exists, or $default otherwise.
   *
   * If $list is not an array or ArrayAccess object, $default is returned.
   *
   * @deprecated
   * In most cases this can be replaced with
   *   $list[$key] ?? $default
   * with the minor difference that when $list[$key] exists and is NULL, this function will always
   * return NULL.
   *
   * @param string $key
   *   Key value to look up in the array.
   * @param array|ArrayAccess $list
   *   Array from which to look up a value.
   * @param mixed $default
   *   (optional) Value to return when $list[$key] does not exist. If $default
   *   is not specified, NULL is used.
   *
   * @return mixed
   *   Can return any type, since $list might contain anything.
   */
  public static function value($key, $list, $default = NULL) {
    if (is_array($list)) {
      return array_key_exists($key, $list) ? $list[$key] : $default;
    }
    if ($list instanceof ArrayAccess) {
      return $list->offsetExists($key) ? $list[$key] : $default;
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
  public static function retrieveValueRecursive(array $params, string $key) {
    // Note that !empty means funky handling for 0
    // but it is 'baked in'. We should probably deprecate this
    // for a more logical approach.
    // see https://github.com/civicrm/civicrm-core/pull/19478#issuecomment-785388559
    if (!empty($params[$key])) {
      return $params[$key];
    }
    foreach ($params as $subParam) {
      if (is_array($subParam) &&
        // @todo - this will mishandle values like 0 and false
        // but it's a little scary to fix.
        $value = self::retrieveValueRecursive($subParam, $key)
      ) {
        return $value;
      }
    }
    return NULL;
  }

  /**
   * Recursively searches through a given array for all matches
   *
   * @param array|null $collection
   * @param array|callable|string $predicate
   * @return array
   */
  public static function findAll($collection, $predicate) {
    $results = [];
    $search = function($collection) use (&$search, &$results, $predicate) {
      if (is_array($collection)) {
        if (is_callable($predicate)) {
          if ($predicate($collection)) {
            $results[] = $collection;
          }
        }
        elseif (is_array($predicate)) {
          if (count(array_intersect_assoc($collection, $predicate)) === count($predicate)) {
            $results[] = $collection;
          }
        }
        else {
          if (array_key_exists($predicate, $collection)) {
            $results[] = $collection;
          }
        }
        foreach ($collection as $item) {
          $search($item);
        }
      }
    };
    $search($collection);
    return $results;
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
   * @param string $separator
   *   (optional) String to be appended after open/close tags.
   *
   * @return string
   *   XML fragment representing $list.
   */
  public static function &xml(&$list, $depth = 1, $separator = "\n") {
    $xml = '';
    foreach ($list as $name => $value) {
      $xml .= str_repeat(' ', $depth * 4);
      if (is_array($value)) {
        $xml .= "<{$name}>{$separator}";
        $xml .= self::xml($value, $depth + 1, $separator);
        $xml .= str_repeat(' ', $depth * 4);
        $xml .= "</{$name}>{$separator}";
      }
      else {
        // make sure we escape value
        $value = self::escapeXML($value);
        $xml .= "<{$name}>$value</{$name}>{$separator}";
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
      $src = ['&', '<', '>', ''];
      $dst = ['&amp;', '&lt;', '&gt;', ','];
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
   * @param string $separator
   *   (optional) String that separates the concatenated keys.
   */
  public static function flatten(&$list, &$flat, $prefix = '', $separator = ".") {
    foreach ($list as $name => $value) {
      $newPrefix = ($prefix) ? $prefix . $separator . $name : $name;
      if (is_array($value)) {
        self::flatten($value, $flat, $newPrefix, $separator);
      }
      else {
        $flat[$newPrefix] = $value;
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
    $result = [];
    foreach ($arr as $key => $value) {
      $path = explode($delim, $key);
      $node = &$result;
      while (count($path) > 1) {
        $key = array_shift($path);
        if (!isset($node[$key])) {
          $node[$key] = [];
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

    $a3 = [];
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
        $ret = self::crmInArray($value, $item, $caseInsensitive);
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
   * This function is used by by import functions and some webforms.
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
    $newLook = [];
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
  public static function crmIsEmptyArray($array = []) {
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
   * @param string|array $field
   *   Name of the attribute used for sorting.
   *
   * @return array
   *   Sorted array
   */
  public static function crmArraySortByField($array, $field) {
    $fields = (array) $field;
    uasort($array, function ($a, $b) use ($fields) {
      foreach ($fields as $f) {
        $f1 = $a[$f] ?? '';
        $f2 = $b[$f] ?? '';
        $v = strnatcmp($f1, $f2);
        if ($v !== 0) {
          return $v;
        }
      }
      return 0;
    });
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
  public static function asort($array = []) {
    $lcMessages = CRM_Utils_System::getUFLocale();

    if ($lcMessages && $lcMessages != 'en_US' && class_exists('Collator')) {
      $collator = new Collator($lcMessages . '.utf8');
      $collator->asort($array);
    }
    elseif (version_compare(PHP_VERSION, '8', '<') && class_exists('Collator')) {
      $collator = new Collator('en_US.utf8');
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

    $result = [];
    foreach ($records as $record) {
      $node = &$result;
      foreach ($keys as $key) {
        if (is_array($record)) {
          $keyvalue = $record[$key] ?? NULL;
        }
        else {
          $keyvalue = $record->{$key} ?? NULL;
        }
        if (isset($node[$keyvalue]) && !is_array($node[$keyvalue])) {
          $node[$keyvalue] = [];
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
    $result = [];
    if (is_array($records)) {
      foreach ($records as $key => $record) {
        if (is_object($record)) {
          $result[$key] = $record->{$prop};
        }
        else {
          $result[$key] = self::value($prop, $record);
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
  public static function collectMethod($method, $objects, $args = []) {
    $result = [];
    if (is_array($objects)) {
      foreach ($objects as $key => $object) {
        $result[$key] = call_user_func_array([$object, $method], $args);
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
    if ($values === '' || $values === "$delim$delim") {
      return [];
    }
    return explode($delim, trim((string) $values, $delim));
  }

  /**
   * Joins array elements with a string, adding surrounding delimiters.
   *
   * This method works mostly like PHP's built-in implode(), but the generated
   * string is surrounded by delimiter characters. Also, if NULL or '' is passed as
   * the $values parameter, it is returned unchanged.
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
    if ($values === NULL || $values === '') {
      return $values;
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
  public static function product($dimensions, $template = []) {
    if (empty($dimensions)) {
      return [$template];
    }

    foreach ($dimensions as $key => $value) {
      $firstKey = $key;
      $firstValues = $value;
      break;
    }
    unset($dimensions[$key]);

    $results = [];
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
    $result = [];
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
   *   Ex: ['foo' => 'bar'].
   * @param string $keyName
   *   Ex: 'key'.
   * @param string $valueName
   *   Ex: 'value'.
   * @return array
   *   Ex: [0 => ['key' => 'foo', 'value' => 'bar']].
   */
  public static function makeNonAssociative($associative, $keyName = 'key', $valueName = 'value') {
    $output = [];
    foreach ($associative as $key => $val) {
      $output[] = [$keyName => $key, $valueName => $val];
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
    $arrayDiff = [];
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
    $newRows = [];
    foreach ($matrix as $pos => $oldRow) {
      $newRow = [];
      foreach ($columns as $column) {
        $newRow[$column] = $oldRow[$column] ?? NULL;
      }
      $newRows[$pos] = $newRow;
    }
    return $newRows;
  }

  /**
   * Rotate a matrix, converting from row-oriented array to a column-oriented array.
   *
   * @param iterable $rows
   *   Ex: [['a'=>10,'b'=>'11'], ['a'=>20,'b'=>21]]
   *   Formula: [scalar $rowId => [scalar $colId => mixed $value]]
   * @param bool $unique
   *   Only return unique values.
   * @return array
   *   Ex: ['a'=>[10,20], 'b'=>[11,21]]
   *   Formula: [scalar $colId => [scalar $rowId => mixed $value]]
   *   Note: In unique mode, the $rowId is not meaningful.
   */
  public static function asColumns(iterable $rows, bool $unique = FALSE) {
    $columns = [];
    foreach ($rows as $rowKey => $row) {
      foreach ($row as $columnKey => $value) {
        if (FALSE === $unique) {
          $columns[$columnKey][$rowKey] = $value;
        }
        elseif (!in_array($value, $columns[$columnKey] ?? [])) {
          $columns[$columnKey][] = $value;
        }
      }
    }
    return $columns;
  }

  /**
   * Rewrite the keys in an array.
   *
   * @param array $array
   * @param string|callable $indexBy
   *   Either the value to key by, or a function($key, $value) that returns the new key.
   * @return array
   */
  public static function rekey($array, $indexBy) {
    $result = [];
    foreach ($array as $key => $value) {
      $newKey = is_callable($indexBy) ? $indexBy($key, $value) : $value[$indexBy];
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
   * Get a single value from an array-tree.
   *
   * @param array $values
   *   Ex: ['foo' => ['bar' => 123]].
   * @param array $path
   *   Ex: ['foo', 'bar'].
   * @param mixed $default
   * @return mixed
   *   Ex 123.
   */
  public static function pathGet($values, $path, $default = NULL) {
    foreach ($path as $key) {
      if (!is_array($values) || !isset($values[$key])) {
        return $default;
      }
      $values = $values[$key];
    }
    return $values;
  }

  /**
   * Check if a key isset which may be several layers deep.
   *
   * This is a helper for when the calling function does not know how many layers deep
   * the path array is so cannot easily check.
   *
   * @param array $values
   * @param array $path
   * @return bool
   */
  public static function pathIsset($values, $path) {
    if ($path === []) {
      return ($values !== NULL);
    }
    foreach ($path as $key) {
      if (!is_array($values) || !isset($values[$key])) {
        return FALSE;
      }
      $values = $values[$key];
    }
    return TRUE;
  }

  /**
   * Remove a key from an array.
   *
   * This is a helper for when the calling function does not know how many layers deep
   * the path array is so cannot easily check.
   *
   * @param array $values
   * @param array $path
   * @param bool $cleanup
   *   If removed item leaves behind an empty array, should you remove the empty array?
   * @return bool
   *   TRUE if anything has been removed. FALSE if no changes were required.
   */
  public static function pathUnset(&$values, $path, $cleanup = FALSE) {
    if (count($path) === 0) {
      $values = NULL;
      return TRUE;
    }

    if (count($path) === 1) {
      if (isset($values[$path[0]])) {
        unset($values[$path[0]]);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      $next = array_shift($path);
      $r = static::pathUnset($values[$next], $path, $cleanup);
      if ($cleanup && $values[$next] === []) {
        $r = TRUE;
        unset($values[$next]);
      }
      return $r;
    }
  }

  /**
   * Set a single value in an array tree.
   *
   * @param array $values
   *   Ex: ['foo' => ['bar' => 123]].
   * @param array $pathParts
   *   Ex: ['foo', 'bar'].
   * @param $value
   *   Ex: 456.
   */
  public static function pathSet(&$values, $pathParts, $value) {
    if ($pathParts === []) {
      $values = $value;
      return;
    }
    $r = &$values;
    $last = array_pop($pathParts);
    foreach ($pathParts as $part) {
      if (!isset($r[$part])) {
        $r[$part] = [];
      }
      $r = &$r[$part];
    }
    $r[$last] = $value;
  }

  /**
   * Move an item in an array-tree (if it exists).
   *
   * @param array $values
   *   Data-tree
   * @param string[] $src
   *   Old path for the existing item
   * @param string[] $dest
   *   New path
   * @param bool $cleanup
   * @return int
   *   Number of items moved (0 or 1).
   */
  public static function pathMove(&$values, $src, $dest, $cleanup = FALSE) {
    if (!static::pathIsset($values, $src)) {
      return 0;
    }
    else {
      $value = static::pathGet($values, $src);
      static::pathSet($values, $dest, $value);
      static::pathUnset($values, $src, $cleanup);
      return 1;
    }
  }

  /**
   * Attempt to synchronize or fill aliased items.
   *
   * If $canonPath is set, copy to $altPath; or...
   * If $altPath is set, copy to $canonPath.
   *
   * @param array $params
   *   Data-tree
   * @param string[] $canonPath
   *   Preferred path
   * @param string[] $altPath
   *   Old/alternate/deprecated path.
   * @param callable|null $filter
   *   Optional function to filter the value as it passes through (canonPath=>altPath or altPath=>canonPath).
   *   function(mixed $v, bool $isCanon): mixed
   */
  public static function pathSync(&$params, $canonPath, $altPath, ?callable $filter = NULL) {
    $MISSING = new \stdClass();

    $v = static::pathGet($params, $canonPath, $MISSING);
    if ($v !== $MISSING) {
      static::pathSet($params, $altPath, $filter ? $filter($v, TRUE) : $v);
      return;
    }

    $v = static::pathGet($params, $altPath, $MISSING);
    if ($v !== $MISSING) {
      static::pathSet($params, $canonPath, $filter ? $filter($v, FALSE) : $v);
      return;
    }
  }

  /**
   * Take one well-defined item out of a single-item list.
   *
   * Assert that the list genuinely contains *exactly* one item.
   *
   * @param iterable $items
   * @param string $recordTypeLabel
   * @return mixed
   *   The first (and only) item from the $items list.
   * @throws \CRM_Core_Exception
   */
  public static function single(iterable $items, string $recordTypeLabel = 'record') {
    $result = NULL;
    foreach ($items as $values) {
      if ($result === NULL) {
        $result = $values;
      }
      else {
        throw new \CRM_Core_Exception("Expected to find one {$recordTypeLabel}, but there were multiple.");
      }
    }

    if ($result === NULL) {
      throw new \CRM_Core_Exception("Expected to find one {$recordTypeLabel}, but there were zero.");
    }

    return $result;
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
   * @deprecated
   */
  public static function toKeyValueRows($array, $keyField = 'key', $valueField = 'value') {
    return self::makeNonAssociative($array, $keyField, $valueField);
  }

  /**
   * Convert array where key(s) holds the actual value and value(s) as 1 into array of actual values
   *  Ex: array('foobar' => 1, 4 => 1) formatted into array('foobar', 4)
   *
   * @deprecated use convertCheckboxInputToArray instead (after testing)
   * https://github.com/civicrm/civicrm-core/pull/8169
   *
   * @param array $array
   */
  public static function formatArrayKeys(&$array) {
    if (!is_array($array)) {
      return;
    }
    $keys = array_keys($array, 1);
    if (count($keys) > 1 ||
      (count($keys) == 1 &&
        (current($keys) > 1 ||
          is_string(current($keys)) ||
          // handle (0 => 4), (1 => 1)
          (current($keys) == 1 && $array[1] == 1)
        )
      )
    ) {
      $array = $keys;
    }
  }

  /**
   * Convert the data format coming in from checkboxes to an array of values.
   *
   * The input format from check boxes looks like
   *   array('value1' => 1, 'value2' => 1). This function converts those values to
   *   array(''value1', 'value2).
   *
   * The function will only alter the array if all values are equal to 1.
   *
   * @param array $input
   *
   * @return array
   */
  public static function convertCheckboxFormatToArray($input) {
    if (isset($input[0])) {
      return $input;
    }
    $keys = array_keys($input, 1);
    if ((count($keys) == count($input))) {
      return $keys;
    }
    return $input;
  }

  /**
   * Ensure that array is encoded in utf8 format.
   *
   * @param array $array
   *
   * @return array $array utf8-encoded.
   */
  public static function encode_items($array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $array[$key] = self::encode_items($value);
      }
      elseif (is_string($value)) {
        $array[$key] = mb_convert_encoding($value, mb_detect_encoding($value, mb_detect_order(), TRUE), 'UTF-8');
      }
      else {
        $array[$key] = $value;
      }
    }
    return $array;
  }

  /**
   * Build tree of elements.
   *
   * @param array $elements
   * @param int|null $parentId
   *
   * @return array
   */
  public static function buildTree($elements, $parentId = NULL) {
    $branch = [];

    foreach ($elements as $element) {
      if ($element['parent_id'] == $parentId) {
        $children = self::buildTree($elements, $element['id']);
        if ($children) {
          $element['children'] = $children;
        }
        $branch[] = $element;
      }
    }

    return $branch;
  }

  /**
   * Find search string in tree.
   *
   * @param string $search
   * @param array $tree
   * @param string $field
   *
   * @return array|null
   */
  public static function findInTree($search, $tree, $field = 'id') {
    foreach ($tree as $item) {
      if ($item[$field] == $search) {
        return $item;
      }
      if (!empty($item['children'])) {
        $found = self::findInTree($search, $item['children']);
        if ($found) {
          return $found;
        }
      }
    }
    return NULL;
  }

  /**
   * Prepend string prefix to every key in an array
   *
   * @param array $collection
   * @param string $prefix
   * @return array
   */
  public static function prefixKeys(array $collection, string $prefix): array {
    $result = [];
    foreach ($collection as $key => $value) {
      $result[$prefix . $key] = $value;
    }
    return $result;
  }

  /**
   * Removes all items from an array whose keys have a given prefix, and returns them unprefixed.
   *
   * @param array $collection
   * @param string $prefix
   */
  public static function filterByPrefix(array &$collection, string $prefix): array {
    $filtered = [];
    foreach (array_keys($collection) as $key) {
      if (!$prefix || strpos($key, $prefix) === 0) {
        $filtered[substr($key, strlen($prefix))] = $collection[$key];
        unset($collection[$key]);
      }
    }
    return $filtered;
  }

  /**
   * Changes array keys to meet the expectations of select2.js
   *
   * @param array $options
   * @param string $label
   * @param string $id
   * @return array
   */
  public static function formatForSelect2(array $options, string $label = 'label', string $id = 'id'): array {
    foreach ($options as &$option) {
      if (isset($option[$label])) {
        $option['text'] = (string) $option[$label];
      }
      if (isset($option[$id])) {
        $option['id'] = (string) $option[$id];
      }
      if (!empty($option['children'])) {
        $option['children'] = self::formatForSelect2($option['children'], $label, $id);
      }
      $option = array_intersect_key($option, array_flip(['id', 'text', 'children', 'color', 'icon', 'description']));
    }
    return $options;
  }

}
