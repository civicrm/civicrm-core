-- Fix invalid api actions in Job table and insert missing job
UPDATE `civicrm_job`
SET api_action = 'process_membership_reminder_date' WHERE api_action = 'process_process_membership_reminder_date';
UPDATE `civicrm_job`
SET api_action = 'mail_report' WHERE api_action = 'mail_reports';

SELECT @domainID := min(id) FROM civicrm_domain;
INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_prefix, api_entity, api_action, parameters, is_active )
VALUES
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Process Survey Respondents{/ts}',   '{ts escape="sql" skip="true"}Releases reserved survey respondents when they have been reserved for longer than the Release Frequency days specified for that survey.{/ts}','civicrm_api3', 'job', 'process_respondent','version=3\r\n', 0);

-- Job table was initially delivered with invalid and not-used parameters. Clearing them out. Most jobs do not need any parameters.
UPDATE `civicrm_job`
SET parameters = NULL;

-- Insert Schedule Jobs admin menu item
SELECT @systemSettingsID := id     FROM civicrm_navigation where name = 'System Settings' AND domain_id = @domainID;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/job&reset=1', '{ts escape="sql" skip="true"}Scheduled Jobs{/ts}', 'Scheduled Jobs', 'administer CiviCRM', '', @systemSettingsID, '1', NULL, 15 );

-- CRM-9468
-- update Serbia/Montenegro provinces
UPDATE civicrm_state_province SET country_id = 1243 WHERE id = 5112;
UPDATE civicrm_state_province SET country_id = 1242 WHERE id = 5113;
UPDATE civicrm_state_province SET country_id = 1242 WHERE id = 5114;
UPDATE civicrm_state_province SET country_id = 1242 WHERE id = 5115;

-- CRM-9523
ALTER TABLE `civicrm_note` MODIFY `privacy` VARCHAR(255) COMMENT 'Foreign Key to Note Privacy Level (which is an option value pair and hence an implicit FK)';
