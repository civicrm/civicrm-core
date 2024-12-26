{* file to handle db changes in 5.79.alpha1 during upgrade *}

UPDATE `civicrm_contribution_page` SET `created_date` = current_timestamp() WHERE `created_date` IS NULL;
ALTER TABLE `civicrm_contribution_page` MODIFY COLUMN `created_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date and time that contribution page was created.';

UPDATE `civicrm_batch` SET `created_date` = current_timestamp() WHERE `created_date` IS NULL;
UPDATE `civicrm_batch` SET `modified_date` = `created_date` WHERE `modified_date` IS NULL;

ALTER TABLE `civicrm_batch` MODIFY COLUMN `created_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When was this item created';
ALTER TABLE `civicrm_batch` MODIFY COLUMN `modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When was this item modified';

UPDATE `civicrm_survey` SET `created_date` = current_timestamp() WHERE `created_date` IS NULL;
UPDATE `civicrm_survey` SET `last_modified_date` = `created_date` WHERE `last_modified_date` IS NULL;

ALTER TABLE `civicrm_survey` MODIFY COLUMN `created_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date and time that Survey was created.';
ALTER TABLE `civicrm_survey` MODIFY COLUMN `last_modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date and time that Survey was edited last time.';

UPDATE `civicrm_campaign` SET `created_date` = current_timestamp() WHERE `created_date` IS NULL;
UPDATE `civicrm_campaign` SET `last_modified_date` = `created_date` WHERE `last_modified_date` IS NULL;

ALTER TABLE `civicrm_campaign` MODIFY COLUMN `created_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date and time that Campaign was created.';
ALTER TABLE `civicrm_campaign` MODIFY COLUMN `last_modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date and time that Campaign was edited last time.';
