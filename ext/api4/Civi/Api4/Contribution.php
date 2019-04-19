<?php

namespace Civi\Api4;

/**
 * Contribution entity.
 *
 * @package Civi\Api4
 */
class Contribution extends Generic\DAOEntity {

  /**
   * @return \Civi\Api4\Action\Contribution\Create
   */
  public static function create() {
    return new \Civi\Api4\Action\Contribution\Create(__CLASS__, __FUNCTION__);
  }

}
