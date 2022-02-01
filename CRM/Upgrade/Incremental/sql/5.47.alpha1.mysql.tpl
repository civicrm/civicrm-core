{* file to handle db changes in 5.47.alpha1 during upgrade *}

CREATE TABLE IF NOT EXISTS `civicrm_queue` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT 'Name of the queue',
  `type` varchar(64) NOT NULL COMMENT 'Type of the queue',
  `is_autorun` tinyint COMMENT 'Should the standard background attempt to autorun tasks in this queue?',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `UI_name`(name)
)
ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

UPDATE `civicrm_navigation` SET `is_active` = 0 WHERE `is_active` IS NULL;
UPDATE `civicrm_navigation` SET `weight` = 0 WHERE `weight` IS NULL;
ALTER TABLE `civicrm_navigation`
  MODIFY COLUMN `is_active` tinyint NOT NULL DEFAULT 1 COMMENT 'Is this navigation item active?',
  MODIFY COLUMN `weight` int NOT NULL DEFAULT 0 COMMENT 'Ordering of the navigation items in various blocks.';

{* https://lab.civicrm.org/dev/core/-/issues/2122 *}
UPDATE `civicrm_event` SET `start_date` = NULL WHERE `start_date` < 19700102;
UPDATE `civicrm_event` SET `end_date` = NULL WHERE `end_date` < 19700102;
UPDATE `civicrm_event` SET `registration_start_date` = NULL WHERE `registration_start_date` < 19700102;
UPDATE `civicrm_event` SET `registration_end_date` = NULL WHERE `registration_end_date` < 19700102;
ALTER TABLE `civicrm_event`
  MODIFY COLUMN `start_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that event starts.',
  MODIFY COLUMN `end_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that event ends. May be NULL if no defined end date/time',
  MODIFY COLUMN `registration_start_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that online registration starts.',
  MODIFY COLUMN `registration_end_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that online registration ends.';

{* Ensure CustomGroup.name is unique *}
UPDATE civicrm_custom_group g1, civicrm_custom_group g2 SET g1.name = CONCAT(g1.name, '_1') WHERE g1.name = g2.name AND g1.id > g2.id;
