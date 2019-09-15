<?php

namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * MockArrayEntity entity.
 *
 * @method Generic\BasicGetAction get()
 *
 * @package Civi\Api4
 */
class MockArrayEntity extends Generic\AbstractEntity {

  public static function getFields() {
    return new BasicGetFieldsAction(static::class, __FUNCTION__, function() {
      return [];
    });
  }

}
