{* file to handle db changes in 4.4.beta5 during upgrade *}
-- CRM-13571
UPDATE civicrm_state_province SET name = 'Møre og Romsdal' WHERE name = 'Møre ag Romsdal';