{* file to handle db changes in 4.7.beta7 during upgrade *}

-- CRM-17800
INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES (NULL, 1187, "04", "Ash Sharqiyah");
UPDATE civicrm_state_province SET name = 'Al Bahah' WHERE name = 'Al Batah';
UPDATE civicrm_state_province SET name = 'Al Hudud Ash Shamaliyah' WHERE name = 'Al H,udd ash Shamallyah';

