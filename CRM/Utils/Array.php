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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Utils_Array {

  /**
   * if the key exists in the list returns the associated value
   *
   * @access public
   *
   * @param array  $list  the array to be searched
   * @param string $key   the key value
   *
   * @return value if exists else null
   * @static
   * @access public
   *
   */
  static function value($key, $list, $default = NULL) {
    if (is_array($list)) {
      return array_key_exists($key, $list) ? $list[$key] : $default;
    }
    return $default;
  }

  /**
   * Given a parameter array and a key to search for,
   * search recursively for that key's value.
   *
   * @param array $values     The parameter array
   * @param string $key       The key to search for
   *
   * @return mixed            The value of the key, or null.
   * @access public
   * @static
   */
  static function retrieveValueRecursive(&$params, $key) {
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
   * if the value exists in the list returns the associated key
   *
   * @access public
   *
   * @param list  the array to be searched
   * @param value the search value
   *
   * @return key if exists else null
   * @static
   * @access public
   *
   */
  static function key($value, &$list) {
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

  static function &xml(&$list, $depth = 1, $seperator = "\n") {
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

  static function escapeXML($value) {
    static $src = NULL;
    static $dst = NULL;

    if (!$src) {
      $src = array('&', '<', '>', '');
      $dst = array('&amp;', '&lt;', '&gt;', ',');
    }

    return str_replace($src, $dst, $value);
  }

  /**
   * Convert an array-tree to a flat array
   *
   * @param array $list the original, tree-shaped list
   * @param array $flat the flat list to which items will be copied
   * @param string $prefix
   * @param string $seperator
   */
  static function flatten(&$list, &$flat, $prefix = '', $seperator = ".") {
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
   * Convert an array with path-like keys into a tree of arrays
   *
   * @param $delim A path delimiter
   * @param $arr A one-dimensional array indexed by string keys
   *
   * @return array-encoded tree
   */
  function unflatten($delim, &$arr) {
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
   * Funtion to merge to two arrays recursively
   *
   * @param array $a1
   * @param array $a2
   *
   * @return  $a3
   * @static
   */
  static function crmArrayMerge($a1, $a2) {
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

  static function isHierarchical(&$list) {
    foreach ($list as $n => $v) {
      if (is_array($v)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Array deep copy
   *
   * @params  array  $array
   * @params  int    $maxdepth
   * @params  int    $depth
   *
   * @return  array  copy of the array
   *
   * @static
   * @access public
   */
  static function array_deep_copy(&$array, $maxdepth = 50, $depth = 0) {
    if ($depth > $maxdepth) {
      return $array;
    }
    $copy = array();
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        array_deep_copy($value, $copy[$key], $maxdepth, ++$depth);
      }
      else {
        $copy[$key] = $value;
      }
    }
    return $copy;
  }

  /**
   * In some cases, functions return an array by reference, but we really don't
   * want to receive a reference.
   *
   * @param $array
   * @return mixed
   */
  static function breakReference($array) {
    $copy = $array;
    return $copy;
  }

  /**
   * Array splice function that preserves associative keys
   * defauly php array_splice function doesnot preserve keys
   * So specify start and end of the array that you want to remove
   *
   * @param  array    $params  array to slice
   * @param  Integer  $start
   * @param  Integer  $end
   *
   * @return  void
   * @static
   */
  static function crmArraySplice(&$params, $start, $end) {
    // verify start and end date
    if ($start < 0) {
      $start = 0;
    }
    if ($end > count($params)) {
      $end = count($params);
    }

    $i = 0;

    // procees unset operation
    foreach ($params as $key => $value) {
      if ($i >= $start && $i < $end) {
        unset($params[$key]);
      }
      $i++;
    }
  }

  /**
   * Function for case insensitive in_array search
   *
   * @param $value             value or search string
   * @param $params            array that need to be searched
   * @param $caseInsensitive   boolean true or false
   *
   * @static
   */
  static function crmInArray($value, $params, $caseInsensitive = TRUE) {
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
   * This function is used to convert associative array names to values
   * and vice-versa.
   *
   * This function is used by both the web form layer and the api. Note that
   * the api needs the name => value conversion, also the view layer typically
   * requires value => name conversion
   */
  static function lookupValue(&$defaults, $property, $lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!array_key_exists(strtolower($src), array_change_key_case($defaults, CASE_LOWER))) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    //trim lookup array, ignore . ( fix for CRM-1514 ), eg for prefix/suffix make sure Dr. and Dr both are valid
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
   *  Function to check if give array is empty
   *  @param array $array array that needs to be check for empty condition
   *
   *  @return boolean true is array is empty else false
   *  @static
   */
  static function crmIsEmptyArray($array = array(
    )) {
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
   * Function to determine how many levels in array for multidimensional arrays
   *
   * @param array $array
   *
   * @return integer $levels containing number of levels in array
   * @static
   */
  static function getLevelsArray($array) {
    if (!is_array($array)) {
      return 0;
    }
    $jsonString = json_encode($array);
    $parts      = explode("}", $jsonString);
    $max        = 0;
    foreach ($parts as $part) {
      $countLevels = substr_count($part, "{");
      if ($countLevels > $max) {
        $max = $countLevels;
      }
    }
    return $max;
  }

  /**
   * Function to sort an associative array of arrays by an attribute using natural string compare
   *
   * @param array $array Array to be sorted
   * @param string $field Name of the attribute you want to sort by
   *
   * @return array $array Sorted array
   * @static
   */
  static function crmArraySortByField($array, $field) {
    $code = "return strnatcmp(\$a['$field'], \$b['$field']);";
    uasort($array, create_function('$a,$b', $code));
    return $array;
  }

  /**
   * Recursively removes duplicate values from an multi-dimensional array.
   *
   * @param array $array The input array possibly containing duplicate values.
   *
   * @return array $array The array with duplicate values removed.
   * @static
   */
  static function crmArrayUnique($array) {
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));
    foreach ($result as $key => $value) {
      if (is_array($value)) {
        $result[$key] = self::crmArrayUnique($value);
      }
    }
    return $result;
  }

  /**
   *  Sort an array and maintain index association, use Collate from the
   *  PECL "intl" package, if available, for UTF-8 sorting (ex: list of countries).
   *  On Debian/Ubuntu: apt-get install php5-intl
   *
   *  @param array $array array of values
   *
   *  @return  array  Sorted array
   *  @static
   */
  static function asort($array = array()) {
    $lcMessages = CRM_Utils_System::getUFLocale();

    if ($lcMessages && $lcMessages != 'en_US' && class_exists('Collator')) {
      $collator = new Collator($lcMessages . '.utf8');
      $collator->asort($array);
    }
    else {
      asort($array);
    }

    return $array;
  }

  /**
   * Convenient way to unset a bunch of items from an array
   *
   * @param array $items (reference)
   * @param string/int/array $itemN: other params to this function will be treated as keys (or arrays of keys) to unset
   */
   static function remove(&$items) {
     foreach (func_get_args() as $n => $key) {
       if ($n && is_array($key)) {
         foreach($key as $k) {
           unset($items[$k]);
         }
       }
       elseif ($n) {
         unset($items[$key]);
       }
     }
   }

  /**
   * Build an array-tree which indexes the records in an array
   *
   * @param $keys array of string (properties by which to index)
   * @param $records array of records (objects or assoc-arrays)
   * @return array; multi-dimensional, with one layer for each key
   */
  static function index($keys, $records) {
    $final_key = array_pop($keys);

    $result = array();
    foreach ($records as $record) {
      $node = &$result;
      foreach ($keys as $key) {
        if (is_array($record)) {
          $keyvalue = $record[$key];
        } else {
          $keyvalue = $record->{$key};
        }
        if (!is_array($node[$keyvalue])) {
          $node[$keyvalue] = array();
        }
        $node = &$node[$keyvalue];
      }
      if (is_array($record)) {
        $node[ $record[$final_key] ] = $record;
      } else {
        $node[ $record->{$final_key} ] = $record;
      }
    }
    return $result;
  }

  /**
   * Iterate through a list of records and grab the value of some property
   *
   * @param string $prop
   * @param array $records a list of records (object|array)
   * @return array keys are the original keys of $records; values are the $prop values
   */
  static function collect($prop, $records) {
    $result = array();
    foreach ($records as $key => $record) {
      if (is_object($record)) {
        $result[$key] = $record->{$prop};
      } else {
        $result[$key] = $record[$prop];
      }
    }
    return $result;
  }

  /**
   * Given a list of key-value pairs, combine thme into a single string
   * @param array $pairs e.g. array('a' => '1', 'b' => '2')
   * @param string $l1Delim e.g. ','
   * @param string $l2Delim e.g. '='
   * @return string e.g. 'a=1,b=2'
   */
  static function implodeKeyValue($l1Delim, $l2Delim, $pairs) {
    $exprs = array();
    foreach ($pairs as $key => $value) {
      $exprs[] = $key . $l2Delim . $value;
    }
    return implode($l1Delim, $exprs);
  }

  /**
   * Like explode() but assumes that the $value is padded with $delim on left and right
   *
   * @param string|NULL $value
   * @param string $delim
   * @return array|NULL
   */
  static function explodePadded($value, $delim = CRM_Core_DAO::VALUE_SEPARATOR) {
    if ($value === NULL) {
      return NULL;
    }
    return explode($delim, trim($value, $delim));
  }

  /**
   * Like implode() but assumes that the $value is padded with $delim on left and right
   *
   * @param string|NULL $value
   * @param string $delim
   * @return array|NULL
   */
  static function implodePadded($values, $delim = CRM_Core_DAO::VALUE_SEPARATOR) {
    if ($values === NULL) {
      return NULL;
    }
    return $delim . implode($delim, $values) . $delim;
  }

  /**
   * Function to modify the key in an array without actually changing the order
   * By default when you add an element it is added at the end
   *
   * @param array  $elementArray associated array element
   * @param string $oldKey       old key
   * @param string $newKey       new key
   *
   * @return array
   */
  static function crmReplaceKey(&$elementArray, $oldKey, $newKey) {
    $keys = array_keys($elementArray);
    if (FALSE === $index = array_search($oldKey, $keys)) {
      throw new Exception(sprintf('key "%s" does not exit', $oldKey));
    }
    $keys[$index] = $newKey;
    $elementArray = array_combine($keys, array_values($elementArray));
    return $elementArray;
  }
}

