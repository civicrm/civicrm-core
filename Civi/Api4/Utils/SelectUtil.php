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

namespace Civi\Api4\Utils;

class SelectUtil {

  /**
   * Checks if a field is in the Select array or matches a wildcard pattern in the Select array
   *
   * @param string $field
   * @param array $selects
   * @return bool
   */
  public static function isFieldSelected($field, $selects) {
    if (in_array($field, $selects) || (in_array('*', $selects) && strpos($field, '.') === FALSE)) {
      return TRUE;
    }
    foreach ($selects as $item) {
      if (strpos($item, '*') !== FALSE && self::getMatchingFields($item, [$field])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Filters a list of fieldnames by matching a pattern which may contain * wildcards.
   *
   * For fieldnames joined with a dot (e.g. email.contact_id), wildcards are only allowed after the last dot.
   *
   * @param string $pattern
   * @param array $fieldNames
   * @return array
   */
  public static function getMatchingFields($pattern, $fieldNames) {
    // If the pattern is "select all" then we return all base fields (excluding those with a dot)
    if ($pattern === '*') {
      return array_values(array_filter($fieldNames, function($field) {
        return strpos($field, '.') === FALSE;
      }));
    }
    $dot = strrpos($pattern, '.');
    $prefix = $dot === FALSE ? '' : substr($pattern, 0, $dot + 1);
    $search = $dot === FALSE ? $pattern : substr($pattern, $dot + 1);
    $search = '/^' . str_replace('\*', '.*', preg_quote($search, '/')) . '$/';
    return array_values(array_filter($fieldNames, function($field) use ($search, $prefix) {
      // Exclude fields that don't have the same join prefix
      if (($prefix !== '' && strpos($field, $prefix) !== 0) || substr_count($prefix, '.') !== substr_count($field, '.')) {
        return FALSE;
      }
      // Now strip the prefix and compare field name to the pattern
      return preg_match($search, substr($field, strlen($prefix)));
    }));
  }

}
