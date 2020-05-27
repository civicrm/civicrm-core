{* file to handle db changes in 4.6.7 during upgrade *}

-- CRM-17016 State list for Fiji incomplete
SELECT @country_id := id from civicrm_country where name = 'Fiji' AND iso_code = 'FJ';
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (@country_id, "C", "Central");
