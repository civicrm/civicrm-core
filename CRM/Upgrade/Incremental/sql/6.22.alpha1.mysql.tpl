{* file to handle db changes in 6.22.alpha1 during upgrade *}

DELETE `cpt` FROM `civicrm_payment_token` `cpt`
  INNER JOIN `civicrm_payment_processor` `cpp` ON `cpt`.`payment_processor_id` = `cpp`.`id`
  INNER JOIN `civicrm_payment_processor_type` `cppt` ON `cpp`.`payment_processor_type_id` = `cppt`.`id`
  WHERE `cppt`.`name` = 'FirstData';

DELETE `cpp` FROM `civicrm_payment_processor` `cpp`
  INNER JOIN `civicrm_payment_processor_type` `cppt` ON `cpp`.`payment_processor_type_id` = `cppt`.`id`
  WHERE `cppt`.`name` = 'FirstData';

DELETE FROM `civicrm_payment_processor_type`
  WHERE `name` = 'FirstData';

-- Add the constituent countries of the United Kingdom
INSERT IGNORE INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("England", "GB", "1", "0");
INSERT IGNORE INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Scotland", "GB", "1", "0");
INSERT IGNORE INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Wales", "GB", "1", "0");
INSERT IGNORE INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Northern Ireland", "GB", "1", "0");
