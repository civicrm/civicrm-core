<?php

namespace Civi\Api4;

/**
 * Campaign entity.
 *
 * @package Civi\Api4
 */
class Campaign extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Campaign\Get
   */
  public static function get() {
    return new \Civi\Api4\Action\Campaign\Get(__CLASS__, __FUNCTION__);
  }

}
