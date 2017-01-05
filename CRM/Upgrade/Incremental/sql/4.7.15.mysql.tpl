{* file to handle db changes in 4.7.15 during upgrade *}

-- CRM-19685 (fix for inconsistencies)
UPDATE civicrm_contact SET preferred_mail_format = 'Both' WHERE preferred_mail_format IS NULL;

-- CRM-19626
ALTER TABLE civicrm_price_set ADD min_amount INT(10) UNSIGNED DEFAULT '0' COMMENT 'Minimum Amount required for this set.';
