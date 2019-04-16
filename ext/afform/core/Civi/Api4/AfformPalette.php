<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\BasicGetFieldsAction;

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

  public static function getFields() {
    return new BasicGetFieldsAction('Afform', __FUNCTION__, function() {
      return [
        [
          'name' => 'id',
        ],
        [
          'name' => 'entity',
        ],
        [
          'name' => 'title',
        ],
        [
          'name' => 'template',
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
