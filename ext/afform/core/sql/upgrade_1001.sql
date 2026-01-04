-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+

-- /*******************************************************
-- *
-- * civicrm_afform_submission
-- *
-- * Recorded form submissions
-- *
-- *******************************************************/
CREATE TABLE `civicrm_afform_submission` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Submission ID',
  `contact_id` int unsigned,
  `afform_name` varchar(255) COMMENT 'Name of submitted afform',
  `data` text COMMENT 'IDs of saved entities',
  `submission_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status_id` int unsigned NOT NULL DEFAULT 1 COMMENT 'fk to Afform Submission Status options in civicrm_option_values',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_civicrm_afform_submission_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
