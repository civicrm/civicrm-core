UPDATE `civicrm_job_log` jl
  LEFT JOIN `civicrm_job` j ON j.id = jl.job_id
  SET jl.`run_time` = COALESCE(j.`last_run_end`, j.`last_run`, current_timestamp())
  WHERE jl.`run_time` IS NULL;
ALTER TABLE `civicrm_job_log` MODIFY COLUMN `run_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Log entry date';
