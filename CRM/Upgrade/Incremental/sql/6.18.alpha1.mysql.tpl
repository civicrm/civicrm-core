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

UPDATE civicrm_membership_status s1, civicrm_membership_status s2
  SET s2.name = CONCAT(s2.name, '_', s2.id)
  WHERE s2.name = s1.name AND s2.id > s1.id;

UPDATE civicrm_participant_status_type s1, civicrm_participant_status_type s2
  SET s2.name = CONCAT(s2.name, '_', s2.id)
  WHERE s2.name = s1.name AND s2.id > s1.id;

UPDATE civicrm_option_value v
  INNER JOIN civicrm_option_group g ON v.option_group_id = g.id
  INNER JOIN civicrm_currency c ON v.value = c.name
  SET {localize field='description'}v.description = c.full_name{/localize}
  WHERE g.name = 'currencies_enabled';
