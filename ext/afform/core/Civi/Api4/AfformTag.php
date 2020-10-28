<?php
namespace Civi\Api4;

/**
 * Class AfformTag
 * @package Civi\Api4
 */
class AfformTag extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction('AfformTag', __FUNCTION__, function() {
      return [
        [
          'name' => 'afl-entity',
          'attrs' => ['entity-name', 'matching-rule', 'assigned-values'],
        ],
        [
          'name' => 'afl-name',
          'attrs' => ['contact-id', 'afl-label'],
        ],
        [
          'name' => 'afl-contact-email',
          'attrs' => ['contact-id', 'afl-label'],
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('AfformTag', __FUNCTION__, function() {
      return [
        [
          'name' => 'name',
        ],
        [
          'name' => 'attrs',
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
