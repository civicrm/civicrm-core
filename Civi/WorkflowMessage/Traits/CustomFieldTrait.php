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

namespace Civi\WorkflowMessage\Traits;

/**
 * Adds a block of custom fields, as traditionally used in back office receipts.
 */
trait CustomFieldTrait {

  /**
   * Get a list of custom fields that are 'viewable'.
   *
   * Viewable is defined as
   *  - is_public = TRUE (group level)
   *  - is_view = FALSE (field level).
   *    This indicate a calculated field (which could be private fundraising info)
   *    and has not been historically visible as it is not on the edit form.
   *  - is not acl blocked for the current user (this is used in back office
   *    context so the user is an admin not the recipient).
   *
   * @param string $entity
   * @param array $extendsEntity
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getFilteredCustomFields(string $entity, array $extendsEntity = []): array {
    $fields = $entityFilters = [];
    // Convert from ['ParticipantRole' = [1], 'ParticipantEventType' => 2], ['ParticipantEventName' => 3]
    // to [1 => [1], 2 => [2], 3 = [3]
    if (!empty($extendsEntity)) {
      $entityColumns = \CRM_Core_BAO_CustomGroup::getExtendsEntityColumnIdOptions(NULL, ['values' => ['extends' => $entity]]);
      foreach ($entityColumns as $entityColumn) {
        if (isset($extendsEntity[$entityColumn['name']])) {
          $entityFilters[(int) $entityColumn['id']] = (array) $extendsEntity[$entityColumn['name']];
          foreach ($entityFilters[$entityColumn['id']] as &$value) {
            // Cast to string because we don't want the calling function to have to worry
            // but also the array intersect fails otherwise.
            $value = (string) $value;
          }
        }
      }
    }
    $groupFilters = [
      'is_active' => TRUE,
      'is_public' => TRUE,
      'extends' => $entity,
    ];
    $allGroups = \CRM_Core_BAO_CustomGroup::getAll($groupFilters, \CRM_Core_Permission::VIEW);
    foreach ($allGroups as $group) {
      $entityValueMatches = array_intersect((array) $group['extends_entity_column_value'], ($entityFilters[$group['extends_entity_column_id']] ?? []));
      if ($entityValueMatches || !$entityFilters || empty($group['extends_entity_column_id'])) {
        foreach ($group['fields'] as $field) {
          if (!$field['is_view']) {
            $field += \CRM_Utils_Array::prefixKeys(array_diff_key($group, ['fields' => 1]), 'custom_group_id.');
            $field['custom_group_id'] = $group['id'];
            $fields[$field['id']] = $field;
          }
        }
      }
    }
    return $fields;
  }

  /**
   * Given an entity loaded through apiv4 return an array of custom fields for display.
   *
   * @param array $entityRecord
   * @param string $entity
   * @param array $filters
   *
   * @return array
   * @throws \Brick\Money\Exception\UnknownCurrencyException
   * @throws \CRM_Core_Exception
   */
  protected function getCustomFieldDisplay(array $entityRecord, string $entity, array $filters = []): array {
    // Fetch the fields, filtered by the entity_extends values
    $viewableFields = $this->getFilteredCustomFields($entity, $filters);

    $fields = [];
    foreach ($viewableFields as $fieldSpec) {
      $fieldName = $fieldSpec['custom_group_id.name'] . '.' . $fieldSpec['name'];
      $value = str_replace('&nbsp;', '', \CRM_Core_BAO_CustomField::displayValue($entityRecord[$fieldName], $fieldSpec['id'], $entityRecord['id']));
      // I can't see evidence we have filtered out empty strings here historically
      // but maybe we should?
      $fields[$fieldSpec['custom_group_id.title']][$fieldSpec['label']] = $value;
    }
    return $fields;
  }

}
