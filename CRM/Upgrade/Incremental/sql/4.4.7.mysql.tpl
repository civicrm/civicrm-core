{* file to handle db changes in 4.4.7 during upgrade *}
-- CRM-13571
UPDATE civicrm_state_province SET name = 'Møre og Romsdal' WHERE name = 'M�re og Romsdal';

-- CRM-13604
UPDATE civicrm_state_province SET name = 'Sololá' WHERE name = 'Solol�';