{* file to handle db changes in 4.6.beta3 during upgrade *}
-- CRM-16059
UPDATE civicrm_state_province SET name = 'Dobrich' WHERE name = 'Dobric';
UPDATE civicrm_state_province SET name = 'Yambol' WHERE name = 'Jambol';
UPDATE civicrm_state_province SET name = 'Kardzhali' WHERE name = 'Kardzali';
UPDATE civicrm_state_province SET name = 'Kyustendil' WHERE name = 'Kjstendil';
UPDATE civicrm_state_province SET name = 'Lovech' WHERE name = 'Lovec';
UPDATE civicrm_state_province SET name = 'Smolyan' WHERE name = 'Smoljan';
UPDATE civicrm_state_province SET name = 'Shumen' WHERE name = 'Sumen';
UPDATE civicrm_state_province SET name = 'Targovishte' WHERE name = 'Targoviste';
UPDATE civicrm_state_province SET name = 'Vratsa' WHERE name = 'Vraca';
