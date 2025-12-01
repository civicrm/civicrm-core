{* file to handle db changes in 6.10.alpha1 during upgrade *}

-- 1. Get the bounce_type_id for the spam bounces
SELECT @bounce_type_id := id
FROM civicrm_mailing_bounce_type
WHERE name = 'Spam'
LIMIT 1;

-- 2. Add in the new bounce type patterns
INSERT INTO civicrm_mailing_bounce_pattern(bounce_type_id, pattern) VALUES
(@bounce_type_id, 'unsolicited mail'), (@bounce_type_id, 'Complaint via SES');
