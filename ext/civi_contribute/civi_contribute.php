<?php

require_once 'civi_contribute.civix.php';

function _civi_contribute_afform_clear() {
  \Civi::cache('metadata')->clear();
}

/**
 * Implements hook_civicrm_selectWhereClause().
 *
 * Grants access to contribution billing addresses.
 */
function civi_contribute_civicrm_selectWhereClause(?string $entityName = NULL, array &$clauses = [], ?int $userId = NULL, array $conditions = []): void {
  if ($entityName === 'Address' && CRM_Core_Permission::check(['access CiviCRM', 'access CiviContribute'], $userId)) {
    $contribSub = CRM_Utils_SQL::mergeSubquery('Contribution', 'address_id');
    if (!empty($contribSub)) {
      foreach ($contribSub as $sub) {
        $clauses['id'][0][] = "IN (SELECT id FROM civicrm_address WHERE contact_id IS NULL AND id $sub)";
      }
    }
    elseif (CRM_Core_Permission::check([['view all contacts', 'edit all contacts']], $userId)) {
      $clauses['id'][0][] = 'IN (SELECT id FROM civicrm_address WHERE contact_id IS NULL AND id IN (SELECT address_id FROM civicrm_contribution WHERE address_id IS NOT NULL))';
    }
  }
}
