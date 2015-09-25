{* file to handle db changes in 4.7.alpha2 during upgrade *}

-- CRM-17221
UPDATE civicrm_state_province SET name = 'Phuket' WHERE name = 'Phaket';