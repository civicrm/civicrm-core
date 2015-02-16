{* file to handle db changes in 4.6.alpha7 during upgrade *}

-- location_type_id should have default NULL, not invalid id 0
ALTER TABLE civicrm_mailing CHANGE `location_type_id` `location_type_id` int(10) unsigned DEFAULT NULL COMMENT 'With email_selection_method, determines which email address to use';


-- CRM-15970 - Track authorship of of A/B tests
ALTER TABLE civicrm_mailing_abtest
  ADD COLUMN `created_id` int unsigned    COMMENT 'FK to Contact ID',
  ADD COLUMN `created_date` datetime    COMMENT 'When was this item created',
  ADD CONSTRAINT FK_civicrm_mailing_abtest_created_id FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL;
