<?php

namespace Civi\Api4\Utils;

use CRM_Utils_Array as UtilsArray;

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
    $parentID = UtilsArray::value('id', $parentArray);

    if ($parentID) {
      $values = array_filter($values, function ($value) use ($parentID) {
        return UtilsArray::value('_parent_id', $value) == $parentID;
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
