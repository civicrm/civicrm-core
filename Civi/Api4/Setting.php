<?php

namespace Civi\Api4;

/**
 * CiviCRM settings api.
 *
 * Used to read/write persistent setting data from CiviCRM.
 *
 * @package Civi\Api4
 */
class Setting extends Generic\AbstractEntity {

  public static function get() {
    return new Action\Setting\Get(__CLASS__, __FUNCTION__);
  }

  public static function set() {
    return new Action\Setting\Set(__CLASS__, __FUNCTION__);
  }

  public static function revert() {
    return new Action\Setting\Revert(__CLASS__, __FUNCTION__);
  }

  public static function getFields() {
    return new Action\Setting\GetFields(__CLASS__, __FUNCTION__);
  }

}
