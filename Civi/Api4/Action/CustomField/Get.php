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

namespace Civi\Api4\Action\CustomField;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\CachedDAOGetAction {

  /**
   * Return all fields for CustomField entity plus all the ones from
   * CustomGroup that we are able to get from the cache.
   *
   * @return array
   */
  protected function getCachedFields(): array {
    $fields = \Civi::entity('CustomField')->getFields();
    $groupFields = \Civi::entity('CustomGroup')->getFields();
    foreach ($groupFields as $name => $groupField) {
      // CachedDAOGetAction can't handle pseudoconstants across joins
      if (!isset($groupField['pseudoconstant'])) {
        $fields["custom_group_id.$name"] = $groupField;
      }
    }
    return $fields;
  }

  /**
   * @inheritdoc
   *
   * Flatten field records from the CustomGroup cache,
   * formatted as CustomField + implicit joins to CustomGroup
   */
  protected function getCachedRecords(): array {
    $records = [];
    $groups = \CRM_Core_BAO_CustomGroup::getAll([], $this->getCheckPermissions() ? \CRM_Core_Permission::VIEW : NULL);
    foreach ($groups as $group) {
      $groupInfo = $group;
      unset($groupInfo['fields']);
      $groupInfo = \CRM_Utils_Array::prefixKeys($groupInfo, 'custom_group_id.');
      foreach ($group['fields'] as $field) {
        $records[] = $field + $groupInfo;
      }
    }
    return $records;
  }

}
