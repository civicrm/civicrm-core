<?php

namespace Civi\Api4;

/**
 * Relationship entity.
 *
 * @package Civi\Api4
 */
class Relationship extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Relationship\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Relationship\Get(static::class, __FUNCTION__);
  }

}
