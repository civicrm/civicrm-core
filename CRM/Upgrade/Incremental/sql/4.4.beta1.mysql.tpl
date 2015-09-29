{* file to handle db changes in 4.4.beta1 during upgrade *}
-- CRM-13314 Added States for Uruguay
INSERT IGNORE INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
(NULL, 1229, "FL", "Florida"),
(NULL, 1229, "RN", "Rio Negro"),
(NULL, 1229, "SJ", "San Jose");

ALTER TABLE civicrm_email
MODIFY email VARCHAR(254);
