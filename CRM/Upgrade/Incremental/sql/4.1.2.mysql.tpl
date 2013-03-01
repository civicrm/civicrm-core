-- CRM-9795 (fix duplicate option values)

SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @maxValue            := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @clientCaseValue     := value FROM civicrm_option_value WHERE name = 'Add Client To Case' AND option_group_id = @option_group_id_act;

UPDATE civicrm_option_value SET value = @maxValue + 1 WHERE name = 'Add Client To Case' AND option_group_id = @option_group_id_act;

UPDATE civicrm_activity 
INNER JOIN civicrm_case_activity ON civicrm_activity.id = civicrm_case_activity.activity_id
SET   civicrm_activity.activity_type_id = @maxValue + 1
WHERE civicrm_activity.activity_type_id = @clientCaseValue;

-- CRM-9868 Force disable jobs that should only be run manually
UPDATE  civicrm_job
SET     is_active = 0
WHERE   api_action IN ('process_membership_reminder_date','update_greeting');

UPDATE  civicrm_job
SET     description = '{ts escape="sql"}Sets membership renewal reminder dates for current membership records where reminder date is null. This job should never be run automatically as it will cause members to get renewal reminders repeatedly.{/ts}'
WHERE   api_action = 'process_membership_reminder_date';

