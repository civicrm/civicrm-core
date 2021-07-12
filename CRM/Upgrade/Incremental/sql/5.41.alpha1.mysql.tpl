{* file to handle db changes in 5.41.alpha1 during upgrade *}
UPDATE civicrm_acl
SET operation = 'Edit'
WHERE object_table = 'civicrm_custom_group' AND operation = 'View';
