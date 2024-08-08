<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Generic\Traits;

/**
 * A bridge is a small table that provides an intermediary link between two other tables.
 *
 * The API can automatically incorporate a Bridge into a join expression.
 */
trait EntityBridge {

  /**
   * Adds "bridge" info, which should specify an array of two field names from this entity
   *
   * This automatic function can be overridden by adding a getInfo function to the api entity class.
   *
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $bridgeFields = [];
    $entity = \Civi::entity(self::getEntityName());
    foreach ($entity->getFields() as $fieldName => $field) {
      if (!empty($field['entity_reference'])) {
        $bridgeFields[] = $fieldName;
      }
    }
    if (count($bridgeFields) === 2) {
      $info['bridge'] = [
        $bridgeFields[0] => ['to' => $bridgeFields[1]],
        $bridgeFields[1] => ['to' => $bridgeFields[0]],
      ];
    }
    return $info;
  }

}
