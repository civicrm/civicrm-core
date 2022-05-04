{* file to handle db changes in 5.50.alpha1 during upgrade *}

CREATE TABLE IF NOT EXISTS `civicrm_user_job` (
`id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Job ID',
`name` varchar(64) COMMENT 'Unique name for job.',
`created_id` int unsigned COMMENT 'FK to contact table.',
`created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time this job was created.',
`start_date` timestamp NULL COMMENT 'Date and time this import job started.',
`end_date` timestamp NULL COMMENT 'Date and time this import job ended.',
`expires_date` timestamp NULL COMMENT 'Date and time to clean up after this import job (temp table deletion date).',
`status_id` int unsigned NOT NULL,
`type_id` int unsigned NOT NULL,
`queue_id` int unsigned COMMENT 'FK to Queue',
`metadata` text COMMENT 'Data pertaining to job configuration',
PRIMARY KEY (`id`),
UNIQUE INDEX `UI_name`(name),
CONSTRAINT FK_civicrm_user_job_created_id FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL,
CONSTRAINT FK_civicrm_user_job_queue_id FOREIGN KEY (`queue_id`) REFERENCES `civicrm_queue`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Add missing Parishes for Bermuda
SELECT @country_id := id from civicrm_country where name = 'Bermuda' AND iso_code = 'BM';
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'DEV', 'Devonshire');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'HAM', 'Hamilton Parish');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'HA', 'City of Hamilton');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'PAG', 'Paget');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'PEM', 'Pembroke');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'SG', 'Town of St. George');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'SGE', 'Saint George\'s');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'SAN', 'Sandys');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'SMI', 'Smiths');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'SOU', 'Southampton');
INSERT IGNORE INTO `civicrm_state_province` (`country_id`, `abbreviation`, `name`) VALUES (@country_id, 'WAR', 'Warwick');
