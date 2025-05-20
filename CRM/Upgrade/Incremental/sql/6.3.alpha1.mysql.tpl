{* file to handle db changes in 6.3.alpha1 during upgrade *}

{* Snapshot before deleting non-attachment rows from civicrm_entity_file *}
{crmUpgradeSnapshot name='entity_file'}
SELECT id, entity_table, entity_id, file_id FROM civicrm_entity_file WHERE entity_table LIKE "civicrm_value_%";
{/crmUpgradeSnapshot}
