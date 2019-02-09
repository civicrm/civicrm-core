<?php

namespace Civi\Api4;

use Civi\Api4\Action\Afform\Create;
use Civi\Api4\Action\Afform\Delete;
use Civi\Api4\Action\Afform\Get;
use Civi\Api4\Action\Afform\Revert;
use Civi\Api4\Action\Afform\Update;

/**
 * Class Afform
 * @package Civi\Api4
 */
class Afform {

  /**
   * @return \Civi\Api4\Action\Afform\Get
   */
  public static function get() {
    return new Get('Afform');
  }

  /**
   * @return \Civi\Api4\Action\Afform\Revert
   */
  public static function revert() {
    return new Revert('Afform');
  }

  /**
   * @return \Civi\Api4\Action\Afform\Update
   */
  public static function update() {
    return new Update('Afform');
  }

  //  /**
  //   * @return \Civi\Api4\Action\Afform\Create
  //   */
  //  public static function create() {
  //    return new Create('Afform');
  //  }

  //  /**
  //   * @return \Civi\Api4\Action\Afform\Delete
  //   */
  //  public static function delete() {
  //    return new Delete('Afform');
  //  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}
