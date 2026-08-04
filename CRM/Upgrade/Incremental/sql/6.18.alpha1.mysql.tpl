{* file to handle db changes in 6.18.alpha1 during upgrade *}

-- Bulgaria
SELECT @country_id := id FROM civicrm_country WHERE name = 'Bulgaria' AND iso_code = 'BG';
UPDATE civicrm_state_province SET name = 'Sofia (Oblast)' WHERE country_id = @country_id AND abbreviation = '23';
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES (@country_id, '22', 'Sofia (Grad)');

-- France
SELECT @country_id := id FROM civicrm_country WHERE name = 'France' AND iso_code = 'FR';
UPDATE civicrm_state_province SET abbreviation = '2A' WHERE country_id = @country_id AND abbreviation = '20A';
UPDATE civicrm_state_province SET abbreviation = '2B' WHERE country_id = @country_id AND abbreviation = '20B';

-- Namibia
SELECT @country_id := id FROM civicrm_country WHERE name = 'Namibia' AND iso_code = 'NA';
UPDATE civicrm_state_province SET abbreviation = 'KE', name = 'Kavango East' WHERE country_id = @country_id AND abbreviation = 'OK';
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES (@country_id, 'KW', 'Kavango West');

-- Netherlands
SELECT @country_id := id FROM civicrm_country WHERE name = 'Netherlands' AND iso_code = 'NL';
UPDATE civicrm_state_province SET name = 'Drenthe' WHERE country_id = @country_id AND abbreviation = 'DR';

-- Venezuela
SELECT @country_id := id FROM civicrm_country WHERE name = 'Venezuela' AND iso_code = 'VE';
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES
(@country_id, 'F', 'Bolívar'),
(@country_id, 'R', 'Sucre'),
(@country_id, 'Z', 'Amazonas');

-- dev/core#3871 : add activity for 'Write-off' feature for pledges
SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;
SELECT @pledgeCompId := id FROM `civicrm_component` where `name` like 'CiviPledge';

INSERT INTO civicrm_option_value
  (option_group_id,                {localize field='label'}label{/localize}, value,                           name,               weight,                        filter, component_id)
VALUES
    (@option_group_id_activity_type, {localize}'{ts escape="sql"}Pledge write-off{/ts}'{/localize}, @max_val+1, 'Pledge write-off', @max_wt+1, 0, @pledgeCompId);
