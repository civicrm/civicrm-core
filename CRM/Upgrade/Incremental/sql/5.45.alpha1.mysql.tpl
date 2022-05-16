{* file to handle db changes in 5.45.alpha1 during upgrade *}
-- Add in missing province for Philippines and update names as per ISO.
SELECT @PHILIPPINESID := id FROM civicrm_country WHERE name = 'Philippines' AND iso_code='PH';
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, is_active, name) VALUES (@PHILIPPINESID, "DIN", 1, 'Dinagat Islands');
UPDATE civicrm_state_province SET name = 'Davao del Norte' WHERE country_id = @PHILIPPINESID AND abbreviation = "DAV" AND name = "Davao";
UPDATE civicrm_state_province SET name = 'Davao de Oro' WHERE country_id = @PHILIPPINESID AND abbreviation = "COM" AND name = "Compostela Valley";
UPDATE civicrm_state_province SET name = 'Kalinga' WHERE country_id = @PHILIPPINESID AND abbreviation = "KAL" AND name = "Kalinga-Apayso";
UPDATE civicrm_state_province SET name = 'Cotabato' WHERE country_id = @PHILIPPINESID AND abbreviation = "NCO" AND name = "North Cotabato";

-- Add missing state for Colombia
SELECT @country_id := id from civicrm_country where name = 'Colombia' AND iso_code = 'CO';
INSERT IGNORE INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, @country_id, 'HUI', 'Huila');
