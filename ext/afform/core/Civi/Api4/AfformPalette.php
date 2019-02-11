<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetAction;

/**
 * Class AfformPalette
 * @package Civi\Api4
 */
class AfformPalette extends AbstractEntity {

  /**
   * @return BasicGetAction
   */
  public static function get() {
    return new BasicGetAction('AfformPalette', __FUNCTION__, function (BasicGetAction $action) {
      return [
        [
          'id' => 'Parent:afl-name',
          'entity' => 'Parent',
          'title' => 'Name',
          'template' => '<afl-name contact-id="entities.parent.id" afl-label="Name"/>',
        ],
        [
          'id' => 'Parent:afl-address',
          'entity' => 'Parent',
          'title' => 'Address',
          'template' => '<afl-address contact-id="entities.parent.id" afl-label="Address"/>',
        ],
      ];
    });
  }

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
