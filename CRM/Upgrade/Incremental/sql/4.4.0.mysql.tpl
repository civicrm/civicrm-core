{* file to handle db changes in 4.4.0 during upgrade *}
-- CRM-13571
UPDATE civicrm_state_province SET name = 'Møre og Romsdal' WHERE name = 'Møre ag Romsdal';

-- CRM-13604
UPDATE civicrm_state_province SET name = 'Alta Verapaz' WHERE name = 'Alta Verapez';
UPDATE civicrm_state_province SET name = 'Baja Verapaz' WHERE name = 'Baja Verapez';
UPDATE civicrm_state_province SET name = 'Retalhuleu' WHERE name = 'Reta.thuleu';
UPDATE civicrm_state_province SET name = 'Sololá' WHERE name = 'Solol6';
