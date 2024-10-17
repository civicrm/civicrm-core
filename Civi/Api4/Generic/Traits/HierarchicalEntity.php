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
 * A hierarchical entity has nested parent/child levels.
 *
 * A special `_depth` field is available to these entities. Adding it to the select clause
 * will cause returned records to be sorted in nested order.
 *
 * NOTE: In order to use this trait, an entity must have a column that is an EntityReference to itself.
 * Note: Hierarchical sorting is performed in-memory, so this is not suitable for entities with unlimited records.
 */
trait HierarchicalEntity {

  /**
   * Automatically adds "parent_field" info, if it hasn't already been declared via `@parentField` annotation.
   *
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    if (empty($info['parent_field'])) {
      $entityName = self::getEntityName();
      $entity = \Civi::entity($entityName);
      foreach ($entity->getFields() as $fieldName => $field) {
        if (($field['entity_reference']['entity'] ?? NULL) === $entityName) {
          $info['parent_field'] = $fieldName;
          break;
        }
      }
    }
    return $info;
  }

}
