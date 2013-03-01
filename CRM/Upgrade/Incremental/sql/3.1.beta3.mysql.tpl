--Add permission for dashlet access

ALTER TABLE `civicrm_dashboard` ADD `is_reserved` TINYINT NULL DEFAULT '0' COMMENT 'Is this dashlet reserved?';

UPDATE `civicrm_dashboard` SET `permission` = 'access CiviCRM', `is_reserved` = 1 WHERE `id` = 1;

