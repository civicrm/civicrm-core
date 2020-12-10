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
 *
 * Note: at time of writing this trait does nothing except affect the "type" shown in Entity::get() metadata.
 */
trait EntityBridge {

  /**
   * Adds "bridge" info, which should specify an array of two field names from this entity
   *
   * This automatic function can be overridden by annotating the APIv4 entity like
   * `@bridge contact_id group_id`
   *
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    if (!empty($info['dao']) && empty($info['bridge'])) {
      foreach (($info['dao'])::fields() as $field) {
        if (!empty($field['FKClassName']) || $field['name'] === 'entity_id') {
          $info['bridge'][] = $field['name'];
        }
      }
    }
    return $info;
  }

}
