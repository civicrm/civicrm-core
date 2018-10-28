<?php

namespace Civi\Api4;

use Civi\Api4\Action\Entity\Get;
use Civi\Api4\Action\Entity\GetFields;
use Civi\Api4\Action\Entity\GetLinks;
use Civi\Api4\Action\GetActions;

/**
 * Retrieves information about all Api4 entities.
 *
 * @package Civi\Api4
 */
class Entity {

  /**
   * @return Get
   */
  public static function get() {
    return new Get('Entity');
  }

  /**
   * @return GetActions
   */
  public static function getActions() {
    return new GetActions('Entity');
  }

  /**
   * @return GetFields
   */
  public static function getFields() {
    return new GetFields('Entity');
  }

  /**
   * @return GetFields
   */
  public static function getLinks() {
    return new GetLinks('Entity');
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [];
  }

}
