<?php

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
