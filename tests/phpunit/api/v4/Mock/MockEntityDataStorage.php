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


namespace api\v4\Mock;

/**
 * Simple data backend for mock basic api.
 */
class MockEntityDataStorage {

  private static $data = [];

  private static $nextId = 1;

  public static function get() {
    return self::$data;
  }

  public static function write($record) {
    if (empty($record['id'])) {
      $record['id'] = self::$nextId++;
      self::$data[$record['id']] = $record;
    }
    else {
      self::$data[$record['id']] = $record + self::$data[$record['id']];
    }
    return $record;
  }

  public static function delete($record) {
    unset(self::$data[$record['id']]);
    return $record;
  }

}
