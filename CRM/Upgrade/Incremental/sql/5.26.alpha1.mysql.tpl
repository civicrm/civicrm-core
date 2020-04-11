{* file to handle db changes in 5.26.alpha1 during upgrade *}

UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;
ALTER TABLE civicrm_contact MODIFY COLUMN is_deceased TINYINT NOT NULL DEFAULT 0;
