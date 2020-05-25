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

/**
 * Class ArrayInsertionUtil
 *
 * @package Civi\Api4\Utils
 */
class ArrayInsertionUtil {

  /**
   * If the values to be inserted contain a key _parent_id they will only be
   * inserted if the parent node ID matches their ID
   *
   * @param $array
   *   The array to insert the value in
   * @param array $parts
   *   Path to insertion point with structure:
   *   [[ name => is_multiple ], ..]
   * @param mixed $values
   *   The value to be inserted
   */
  public static function insert(&$array, $parts, $values) {
    $key = key($parts);
    $isMulti = array_shift($parts);
    if (!isset($array[$key])) {
      $array[$key] = $isMulti ? [] : NULL;
    }
    if (empty($parts)) {
      $values = self::filterValues($array, $isMulti, $values);
      $array[$key] = $values;
    }
    else {
      if ($isMulti) {
        foreach ($array[$key] as &$subArray) {
          self::insert($subArray, $parts, $values);
        }
      }
      else {
        self::insert($array[$key], $parts, $values);
      }
    }
  }

  /**
   * @param $parentArray
   * @param $isMulti
   * @param $values
   *
   * @return array|mixed
   */
  private static function filterValues($parentArray, $isMulti, $values) {
    $parentID = $parentArray['id'] ?? NULL;

    if ($parentID) {
      $values = array_filter($values, function ($value) use ($parentID) {
        return ($value['_parent_id'] ?? NULL) == $parentID;
      });
    }

    $unsets = ['_parent_id', '_base_id'];
    array_walk($values, function (&$value) use ($unsets) {
      foreach ($unsets as $unset) {
        if (isset($value[$unset])) {
          unset($value[$unset]);
        }
      }
    });

    if (!$isMulti) {
      $values = array_shift($values);
    }
    return $values;
  }

}
