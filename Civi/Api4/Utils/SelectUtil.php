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
 * $Id$
 *
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
   * @param string $pattern
   * @param array $fieldNames
   * @return array
   */
  public static function getMatchingFields($pattern, $fieldNames) {
    if ($pattern === '*') {
      return $fieldNames;
    }
    $pattern = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
    return array_values(array_filter($fieldNames, function($field) use ($pattern) {
      return preg_match($pattern, $field);
    }));
  }

}
