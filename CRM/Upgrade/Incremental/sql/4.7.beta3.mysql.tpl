{* file to handle db changes in 4.7.beta3 during upgrade *}

-- CRM-17660 Add missing Cameroon Provinces and add missing Indian province.
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES
(1038, "LT", "Littoral"),
(1038, "NW", "Nord-Ouest"),
(1101, "TG", "Telangana");

