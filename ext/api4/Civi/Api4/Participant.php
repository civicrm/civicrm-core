<?php

namespace Civi\Api4;

/**
 * Participant entity.
 *
 * @package Civi\Api4
 */
class Participant extends Generic\DAOEntity {

  /**
   * @return Action\Participant\Get
   */
  public static function get() {
    return new Action\Participant\Get(__CLASS__, __FUNCTION__);
  }

}
