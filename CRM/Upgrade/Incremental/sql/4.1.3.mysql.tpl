-- CRM-10264
INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_prefix, api_entity, api_action, parameters, is_active )
VALUES
    ( {$domainID}, 'Hourly', NULL, '{ts escape="sql" skip="true"}Clean-up Temporary Data and Files{/ts}','{ts escape="sql" skip="true"}Removes temporary data and files, and clears old data from cache tables. Recommend running this job every hour to help prevent database and file system bloat.{/ts}','civicrm_api3', 'job', 'cleanup', NULL, 0);
