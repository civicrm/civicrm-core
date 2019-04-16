<?php
namespace Civi\Api4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * Class AfformTag
 * @package Civi\Api4
 */
class AfformTag extends AbstractEntity {

  /**
   * @return BasicGetAction
   */
  public static function get() {
    return new BasicGetAction('AfformTag', __FUNCTION__, function (BasicGetAction $action) {
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
    });
  }

  public static function getFields() {
    return new BasicGetFieldsAction('Afform', __FUNCTION__, function() {
      return [
        [
          'name' => 'name',
        ],
        [
          'name' => 'attrs',
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
