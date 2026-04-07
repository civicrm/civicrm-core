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
 * @deprecated
 *
 * Implement a serialization format where:
 * - Items are comma-separated
 * - Items maybe barewords or "key=value" pairs
 *
 * This format is not well-defined for all possible inputs. It should not be used.
 * It's included for working with legacy data.
 */
class CRM_Utils_CommaKV {

  /**
   * Convert key=val options into an array while keeping
   * compatibility for values only.
   */
  public static function unserialize($string) : array {
    $options = [];
    $temp = explode(',', $string ?? '');

    foreach ($temp as $value) {
      $parts = explode('=', $value, 2);
      if (count($parts) == 2) {
        $options[trim($parts[0])] = trim($parts[1]);
      }
      else {
        $options[trim($value)] = trim($value);
      }
    }

    return $options;
  }

  public static function serialize($values) {
    $parts = [];
    foreach ($values as $key => $value) {
      if ($key === $value) {
        $parts[] = $key;
      }
      else {
        $parts[] = $key . '=' . $value;
      }
    }
    return implode(",\n", $parts);
  }

}
