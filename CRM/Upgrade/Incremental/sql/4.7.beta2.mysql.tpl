{* file to handle db changes in 4.7.beta2 during upgrade *}

-- CRM-17404 NULL values in 'do_not_*' fields if blank in contact:create API
UPDATE civicrm_contact SET do_not_email = 0 WHERE do_not_email IS NULL;
UPDATE civicrm_contact SET do_not_phone = 0 WHERE do_not_phone IS NULL;
UPDATE civicrm_contact SET do_not_mail = 0 WHERE do_not_mail IS NULL;
UPDATE civicrm_contact SET do_not_sms = 0 WHERE do_not_sms IS NULL;
UPDATE civicrm_contact SET do_not_trade = 0 WHERE do_not_trade IS NULL;
ALTER TABLE civicrm_contact ALTER COLUMN do_not_email SET DEFAULT 0;
ALTER TABLE civicrm_contact ALTER COLUMN do_not_phone SET DEFAULT 0;
ALTER TABLE civicrm_contact ALTER COLUMN do_not_mail SET DEFAULT 0;
ALTER TABLE civicrm_contact ALTER COLUMN do_not_sms SET DEFAULT 0;
ALTER TABLE civicrm_contact ALTER COLUMN do_not_trade SET DEFAULT 0;

-- CRM-17147 People with empty deceased-flag ('is null') get removed from recipient list of a mailing
UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;
ALTER TABLE civicrm_contact ALTER COLUMN is_deceased SET DEFAULT 0;

-- CRM-17637
INSERT INTO `civicrm_job`
( domain_id, run_frequency, last_run, name, description, api_entity, api_action, parameters, is_active )
VALUES
( {$domainID}, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}CiviCRM Update Check{/ts}', '{ts escape="sql" skip="true"}Checks for CiviCRM version updates. Important for keeping the database secure. Also sends anonymous usage statistics to civicrm.org to to assist in prioritizing ongoing development efforts.{/ts}', 'job', 'version_check', NULL, 1);
