<?php

namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * A collection of system maintenance/diagnostic utilities.
 *
 * @package Civi\Api4
 */
class System extends Generic\AbstractEntity {

  public static function flush() {
    return new Action\System\Flush(__CLASS__, __FUNCTION__);
  }

  public static function check() {
    return new Action\System\Check(__CLASS__, __FUNCTION__);
  }

  public static function getFields() {
    return new BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    });
  }

}
