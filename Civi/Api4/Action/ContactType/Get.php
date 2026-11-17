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

namespace Civi\Api4\Action\ContactType;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\CachedDAOGetAction {

  /**
   * Return all fields for ContactType entity plus parent_id.name and parent_id.label
   * which are included in the ContactType cache.
   *
   * @return array
   */
  protected function getCachedFields(): array {
    $fields = parent::getCachedFields();
    $fields['parent_id.name'] = $fields['name'];
    $fields['parent_id.label'] = $fields['label'];
    return $fields;
  }

  /**
   * @inheritdoc
   */
  protected function getCachedRecords(): array {
    $records = [];
    foreach (\CRM_Contact_BAO_ContactType::getAllContactTypes() as $record) {
      $record['parent_id.name'] = $record['parent'];
      $record['parent_id.label'] = $record['parent_label'];
      unset($record['parent'], $record['parent_label']);
      $records[] = $record;
    }
    return $records;
  }

}
