<?php

namespace Civi\Api4;

/**
 * Class AfformPalette
 * @package Civi\Api4
 */
class AfformPalette extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction('AfformPalette', __FUNCTION__, function() {
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
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('AfformPalette', __FUNCTION__, function() {
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
    }))->setCheckPermissions($checkPermissions);
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
