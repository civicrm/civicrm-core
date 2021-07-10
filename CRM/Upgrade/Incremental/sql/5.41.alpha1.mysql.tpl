{* file to handle db changes in 5.41.alpha1 during upgrade *}

-- https://lab.civicrm.org/dev/core/-/issues/2122

ALTER TABLE civicrm_event
MODIFY COLUMN `start_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that event starts.',
MODIFY COLUMN `end_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that event ends. May be NULL if no defined end date/time',
MODIFY COLUMN `registration_start_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that online registration starts.',
MODIFY COLUMN `registration_end_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that online registration ends.';
