{* file to handle db changes in 5.67.alpha1 during upgrade *}

UPDATE civicrm_note SET privacy = 0 WHERE privacy IS NULL;

{* NULL values would be nonsensical and useless - no reason to keep them *}
DELETE FROM civicrm_entity_file WHERE entity_table IS NULL;

{* Delete navigation links to old campaign dashboard *}
DELETE FROM `civicrm_navigation` WHERE `url` LIKE 'civicrm/campaign?reset=1%';

SELECT @country_id := id from civicrm_country where name = 'India' AND iso_code = 'IN';
UPDATE `civicrm_state_province` SET abbreviation = 'MH' WHERE name = 'Maharashtra' AND country_id = @country_id;
UPDATE `civicrm_state_province` SET abbreviation = 'CG' WHERE name = 'Chhattisgarh' AND country_id = @country_id;
