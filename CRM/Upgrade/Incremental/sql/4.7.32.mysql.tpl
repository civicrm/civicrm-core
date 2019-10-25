{* file to handle db changes in 4.7.32 during upgrade *}

-- CRM-21837 - Missing states for Gabon
SELECT @country_id := id from civicrm_country where name = 'Gabon' AND iso_code = 'GA';
INSERT IGNORE INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, @country_id, "01", "Estuaire"),
(NULL, @country_id, "02", "Haut-Ogooué"),
(NULL, @country_id, "03", "Moyen-Ogooué"),
(NULL, @country_id, "04", "Ngounié"),
(NULL, @country_id, "05", "Nyanga"),
(NULL, @country_id, "06", "Ogooué-Ivindo"),
(NULL, @country_id, "07", "Ogooué-Lolo"),
(NULL, @country_id, "08", "Ogooué-Maritime"),
(NULL, @country_id, "09", "Woleu-Ntem");
