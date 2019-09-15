<?php

namespace Civi\Api4;

/**
 * Event entity.
 *
 * @package Civi\Api4
 */
class Event extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Event\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Event\Get(__CLASS__, __FUNCTION__);
  }

}
