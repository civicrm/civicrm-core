{* file to handle db changes in 4.7.beta6 during upgrade *}

-- CRM-17686
ALTER TABLE `civicrm_job`
ADD COLUMN `scheduled_run_date` timestamp NULL DEFAULT NULL COMMENT 'When is this cron entry scheduled to run' AFTER `last_run`;
