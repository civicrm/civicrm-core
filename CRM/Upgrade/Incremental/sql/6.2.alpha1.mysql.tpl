{* file to handle db changes in 6.2.alpha1 during upgrade *}

{localize field="title"}
  UPDATE civicrm_custom_group SET name = title
  WHERE name IS NULL;
{/localize}

UPDATE civicrm_custom_group SET extends = 'Contact'
WHERE extends IS NULL;

UPDATE civicrm_custom_group SET style = 'Inline'
WHERE style IS NULL OR style NOT IN ('Tab', 'Inline', 'Tab with table');

{* Remove EntityFile records for custom fields; they are redundant with the value of the field *}
{crmUpgradeSnapshot name='entity_file'}
  SELECT id, entity_table, entity_id, file_id FROM civicrm_entity_file WHERE entity_table LIKE "civicrm_value_%";
{/crmUpgradeSnapshot}
DELETE FROM civicrm_entity_file WHERE entity_table LIKE "civicrm_value_%";
