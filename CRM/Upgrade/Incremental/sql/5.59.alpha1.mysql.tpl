{* file to handle db changes in 5.59.alpha1 during upgrade *}
-- Add missing provinces for Luxembourg
SELECT @country_id := id from civicrm_country where name = 'Luxembourg' AND iso_code = 'LU';
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'CA', 'Capellen');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'CL', 'Clervaux');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'EC', 'Echternach');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'ES', 'Esch-sur-Alzette');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'GR', 'Grevenmacher');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'ME', 'Mersch');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'RD', 'Redange-sur-Attert');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'RM', 'Remich');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'VD', 'Vianden');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'WI', 'Wiltz');
UPDATE `civicrm_state_province` SET abbreviation = 'LU' WHERE name = 'Luxembourg' AND country_id = @country_id;
UPDATE `civicrm_state_province` SET abbreviation = 'DI' WHERE name = 'Diekirch' AND country_id = @country_id;
UPDATE `civicrm_state_province` SET name = 'Grevenmacher' WHERE name = 'GreveNmacher' AND country_id = @country_id;
UPDATE `civicrm_state_province` SET abbreviation = 'GR' WHERE name = 'Grevenmacher' AND country_id = @country_id;
