<?php

namespace Civi\Api4;

/**
 * Navigation entity.
 *
 * @package Civi\Api4
 */
class Navigation extends Generic\DAOEntity {

  /**
   * @return Action\Navigation\Get
   */
  public static function get() {
    return new Action\Navigation\Get(__CLASS__, __FUNCTION__);
  }

}
