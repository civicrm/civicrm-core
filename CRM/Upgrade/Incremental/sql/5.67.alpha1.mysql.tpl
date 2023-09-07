{* file to handle db changes in 5.67.alpha1 during upgrade *}

{* NULL values would be nonsensical and useless - no reason to keep them *}
DELETE FROM civicrm_entity_file WHERE entity_table IS NULL;
