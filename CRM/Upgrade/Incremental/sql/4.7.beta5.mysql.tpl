{* file to handle db changes in 4.7.beta5 during upgrade *}

-- CRM-17686
ALTER TABLE `civicrm_job`
ADD COLUMN `next_scheduled_run` timestamp NULL DEFAULT NULL COMMENT 'When is this cron entry scheduled to run next' AFTER `last_run`;
