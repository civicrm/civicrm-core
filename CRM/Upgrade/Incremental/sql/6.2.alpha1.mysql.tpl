{* file to handle db changes in 6.2.alpha1 during upgrade *}

{* Remove EntityFile records for custom fields; they are redundant with the value of the field *}
DELETE FROM civicrm_entity_file WHERE entity_table LIKE "civicrm_value_%";
