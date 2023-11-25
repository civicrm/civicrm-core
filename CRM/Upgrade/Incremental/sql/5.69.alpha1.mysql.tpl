{* file to handle db changes in 5.69.alpha1 during upgrade *}
-- Add missing provinces for Zambia
SELECT @country_id := id FROM civicrm_country WHERE name = 'Zambia';
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'C', 'Central');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'E', 'Eastern');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'M', 'Muchinga');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'N', 'Northern');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'S', 'Southern');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'W', 'Western');
