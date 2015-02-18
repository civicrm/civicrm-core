{* file to handle db changes in 4.6.alpha7 during upgrade *}

-- location_type_id should have default NULL, not invalid id 0
ALTER TABLE civicrm_mailing CHANGE `location_type_id` `location_type_id` int(10) unsigned DEFAULT NULL COMMENT 'With email_selection_method, determines which email address to use';

-- CRM-15970 - Track authorship of of A/B tests
ALTER TABLE civicrm_mailing_abtest
  ADD COLUMN `created_id` int unsigned    COMMENT 'FK to Contact ID',
  ADD COLUMN `created_date` datetime    COMMENT 'When was this item created',
  ADD COLUMN `testing_criteria` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  ADD COLUMN `winner_criteria` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  ADD CONSTRAINT FK_civicrm_mailing_abtest_created_id FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL;

-- Move A/B test option-values into code
DELETE FROM civicrm_option_group WHERE name IN ('mailing_ab_status', 'mailing_ab_testing_criteria', 'mailing_ab_winner_criteria');
UPDATE civicrm_mailing_abtest SET testing_criteria = 'subject' WHERE testing_criteria_id = 1;
UPDATE civicrm_mailing_abtest SET testing_criteria = 'from' WHERE testing_criteria_id = 2;
UPDATE civicrm_mailing_abtest SET testing_criteria = 'full_email' WHERE testing_criteria_id = 3;
UPDATE civicrm_mailing_abtest SET winner_criteria = 'open' WHERE winner_criteria_id = 1;
UPDATE civicrm_mailing_abtest SET winner_criteria = 'unique_click' WHERE winner_criteria_id = 2;
UPDATE civicrm_mailing_abtest SET winner_criteria = 'link_click' WHERE winner_criteria_id = 3;

ALTER TABLE civicrm_mailing_abtest
  DROP COLUMN `testing_criteria_id`,
  DROP COLUMN `winner_criteria_id`;
