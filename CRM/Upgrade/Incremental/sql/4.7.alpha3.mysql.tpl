{* file to handle db changes in 4.7.alpha3 during upgrade *}
-- CRM-17309
INSERT INTO civicrm_mailing_bounce_pattern
(bounce_type_id,pattern)
VALUES
(3,'Unable to resolve MX record for');
