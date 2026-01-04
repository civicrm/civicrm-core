-- Not accurate but we need to set it to SOMETHING before changing to NOT NULL
UPDATE `civicrm_job_log` jl
  LEFT JOIN `civicrm_job` j ON j.id = jl.job_id
  SET jl.`run_time` = COALESCE(j.`last_run_end`, j.`last_run`, current_timestamp())
  WHERE jl.`run_time` IS NULL;
ALTER TABLE `civicrm_job_log` MODIFY COLUMN `run_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Log entry date';

-- There is no reasonable date to use, but this is just cache anyway and will get cleared at the end of the upgrade.
UPDATE `civicrm_cache` c
  SET c.`created_date` = current_timestamp()
  WHERE c.`created_date` IS NULL;
ALTER TABLE `civicrm_cache` MODIFY COLUMN `created_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When was the cache item created';

-- Not accurate but we need to set it to SOMETHING before changing to NOT NULL
UPDATE `civicrm_contribution_recur` c
  SET c.`modified_date` = COALESCE(c.`create_date`, current_timestamp())
  WHERE c.`modified_date` IS NULL;
ALTER TABLE `civicrm_contribution_recur` MODIFY COLUMN `modified_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last updated date for this record. mostly the last time a payment was received';

-- Not accurate but we need to set it to SOMETHING before changing to NOT NULL
UPDATE `civicrm_note` c
  SET c.`note_date` = COALESCE(c.`created_date`, current_timestamp())
  WHERE c.`note_date` IS NULL;
UPDATE `civicrm_note` c
  SET c.`modified_date` = COALESCE(c.`created_date`, current_timestamp())
  WHERE c.`modified_date` IS NULL;
ALTER TABLE `civicrm_note` MODIFY COLUMN `note_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Date attached to the note';
ALTER TABLE `civicrm_note` MODIFY COLUMN `modified_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When was this note last modified/edited';

-- Not accurate but we need to set it to SOMETHING before changing to NOT NULL
UPDATE `civicrm_payment_token` c
  SET c.`created_date` = current_timestamp()
  WHERE c.`created_date` IS NULL;
ALTER TABLE `civicrm_payment_token` MODIFY COLUMN `created_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Date created';

-- Not accurate but we need to set it to SOMETHING before changing to NOT NULL
UPDATE `civicrm_system_log` c
  SET c.`timestamp` = current_timestamp()
  WHERE c.`timestamp` IS NULL;
ALTER TABLE `civicrm_system_log` MODIFY COLUMN `timestamp` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp of when event occurred.';
