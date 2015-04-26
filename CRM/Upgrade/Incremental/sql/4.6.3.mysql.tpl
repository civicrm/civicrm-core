{* file to handle db changes in 4.6.3 during upgrade *}

-- CRM-16360
UPDATE `civicrm_option_value` SET name = REPLACE(REPLACE(REPLACE(name, '&', '_'), '<', '_'), '>', '_')
WHERE name LIKE '%&%' OR name LIKE '%<%' OR name LIKE '%>%';