{* file to handle db changes in 4.7.29 during upgrade *}

-- CRM-21407 ISO compliance for German counties
UPDATE `civicrm_state_province` SET `name` = 'Baden-Württemberg' WHERE `name` = 'Baden-Wuerttemberg' AND `country_id` = 1082;
UPDATE `civicrm_state_province` SET `abbreviation` = 'BE' WHERE `name` = 'Berlin' AND `abbreviation` = 'BR' AND `country_id` = 1082;
UPDATE `civicrm_state_province` SET `name` = 'Thüringen' WHERE `name` = 'Thueringen' AND `country_id` = 1082;