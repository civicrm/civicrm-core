{* file to handle db changes in 4.7.beta6 during upgrade *}

-- CRM-17686
ALTER TABLE `civicrm_job`
ADD COLUMN `scheduled_run_date` timestamp NULL DEFAULT NULL COMMENT 'When is this cron entry scheduled to run' AFTER `last_run`;

-- CRM-17745: Make maximum additional participants configurable
ALTER TABLE civicrm_event
ADD COLUMN max_additional_participants int(10) unsigned
DEFAULT 0
COMMENT 'Maximum number of additional participants that can be registered on a single booking'
AFTER is_multiple_registrations;
UPDATE civicrm_event
SET max_additional_participants = 9
WHERE is_multiple_registrations = 1;

SELECT @domainID := min(id) FROM civicrm_domain;
INSERT INTO civicrm_setting(name, value, domain_id, is_domain) values ('installed', 'i:1;', @domainID, 1);
