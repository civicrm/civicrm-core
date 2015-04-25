
-- Add new columns for multilingual purpose
ALTER TABLE `civicrm_action_schedule` ADD COLUMN `filter_contact_language` varchar(128) DEFAULT NULL COMMENT 'Used for multilingual installation';
ALTER TABLE `civicrm_action_schedule` ADD COLUMN `communication_language` varchar(8) DEFAULT NULL COMMENT 'Used for multilingual installation';


