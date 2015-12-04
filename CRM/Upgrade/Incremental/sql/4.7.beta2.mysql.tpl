{* file to handle db changes in 4.7.beta2 during upgrade *}

-- CRM-17147 People with empty deceased-flag ('is null') get removed from recipient list of a mailing
UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;
ALTER TABLE civicrm_contact ALTER COLUMN is_deceased SET DEFAULT 0;
