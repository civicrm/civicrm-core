{* file to handle db changes in 5.48.1 during upgrade *}

DELETE FROM civicrm_managed WHERE module = "civigrant" AND entity_type = "OptionValue"
AND name LIKE "OptionGroup_grant_status_%";
