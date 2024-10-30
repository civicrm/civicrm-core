{* file to handle db changes in 5.68.alpha1 during upgrade *}

UPDATE `civicrm_acl` SET `is_active` = 0 WHERE `is_active` IS NULL;
UPDATE `civicrm_dashboard_contact` SET `is_active` = 0 WHERE `is_active` IS NULL;

UPDATE `civicrm_tag` SET `label` = `name` WHERE `label` = '';

{* This column is now required. Delete any null values; a managed entity without a name is useless *}
DELETE FROM civicrm_managed WHERE name IS NULL;
{* This column is now required. Set to default value if null or invalid *}
UPDATE civicrm_managed SET cleanup = 'always' WHERE cleanup IS NULL OR cleanup NOT IN ('always', 'never', 'unused');
