{* file to handle db changes in 4.4.0 during upgrade *}
-- CRM-13571
UPDATE civicrm_state_province SET name = 'M�re og Romsdal' WHERE name = 'M�re ag Romsdal';

-- CRM-13604
UPDATE civicrm_state_province SET name = 'Alta Verapaz' WHERE name = 'Alta Verapez';
UPDATE civicrm_state_province SET name = 'Baja Verapaz' WHERE name = 'Baja Verapez';
UPDATE civicrm_state_province SET name = 'Retalhuleu' WHERE name = 'Reta.thuleu';
UPDATE civicrm_state_province SET name = 'Solol�' WHERE name = 'Solol6';
