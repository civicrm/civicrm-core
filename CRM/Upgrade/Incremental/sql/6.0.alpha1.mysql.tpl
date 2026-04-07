{* file to handle db changes in 6.0.alpha1 during upgrade *}

-- Cleanup after migration from civicrm_option_value 'from_email_address' to civicrm_site_email_address
DELETE ov
FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og
ON ov.option_group_id = og.id
WHERE og.name = 'from_email_address';

DELETE FROM civicrm_option_group WHERE name = 'from_email_address';

UPDATE civicrm_navigation
SET url = 'civicrm/admin/options/site_email_address'
WHERE url LIKE 'civicrm/admin/options/from_email_address%';
