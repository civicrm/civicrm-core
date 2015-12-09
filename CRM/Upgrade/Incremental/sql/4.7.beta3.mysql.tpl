{* file to handle db changes in 4.7.beta3 during upgrade *}

-- CRM-17660 Add missing Cameroon Provinces and add missing Indian province.
INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
(NULL, 1038, "LT", "Littoral"),
(NULL, 1038, "NW", "Nord-Ouest"),
(NULL, 1101, "TG", "Telangana");

