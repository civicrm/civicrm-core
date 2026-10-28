<?php

require_once 'civi_event.civix.php';

/**
 * Implements hook_civicrm_selectWhereClause().
 *
 * Grants access to event location block addresses, emails, phones, and IMs.
 */
function civi_event_civicrm_selectWhereClause(?string $entityName = NULL, array &$clauses = [], ?int $userId = NULL, array $conditions = []): void {
  $locBlockFields = [
    'Address' => ['address_id', 'address_2_id'],
    'Email' => ['email_id', 'email_2_id'],
    'Phone' => ['phone_id', 'phone_2_id'],
    'IM' => ['im_id', 'im_2_id'],
  ];

  if (!empty($entityName) && !empty($locBlockFields[$entityName]) && CRM_Core_Permission::check([['view event info', 'access CiviEvent']], $userId)) {
    $tableName = CRM_Core_DAO_AllCoreTables::getTableForEntityName($entityName);
    foreach ($locBlockFields[$entityName] as $field) {
      $clauses['id'][0][] = "IN (SELECT id FROM $tableName WHERE contact_id IS NULL AND id IN (SELECT $field FROM civicrm_loc_block WHERE $field IS NOT NULL))";
    }
  }
}
