<?php

namespace Civi\Api4;

/**
 * Domains - multisite instances of CiviCRM.
 *
 * @package Civi\Api4
 */
class Domain extends Generic\DAOEntity {

  public static function get() {
    return new \Civi\Api4\Action\Domain\Get(__CLASS__, __FUNCTION__);
  }

}
