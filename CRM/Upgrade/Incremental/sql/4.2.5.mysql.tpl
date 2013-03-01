-- Placeholder which ensures that PHP upgrade tasks are executed

-- Get domain id
SELECT  @domainID := min(id) FROM civicrm_domain;

-- CRM-11060
INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_prefix, api_entity, api_action, parameters, is_active )
VALUES  
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Send Scheduled SMS{/ts}', '{ts escape="sql" skip="true"}Sends out scheduled SMS{/ts}',  'civicrm_api3', 'job', 'process_sms', NULL, 0);
