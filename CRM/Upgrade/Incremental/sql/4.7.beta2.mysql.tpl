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
