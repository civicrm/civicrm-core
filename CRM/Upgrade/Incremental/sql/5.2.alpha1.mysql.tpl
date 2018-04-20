{* file to handle db changes in 5.2.alpha1 during upgrade *}
# CRM-19885 & https://lab.civicrm.org/dev/core/issues/36#note_3509
UPDATE civicrm_action_schedule SET repetition_frequency_interval = 0 WHERE repetition_frequency_interval IS NULL;
UPDATE civicrm_action_schedule SET start_action_offset = 0 WHERE start_action_offset IS NULL;
UPDATE civicrm_action_schedule SET end_frequency_interval = 0 WHERE end_frequency_interval IS NULL;

ALTER TABLE civicrm_action_schedule
ALTER column repetition_frequency_interval SET DEFAULT 0,
ALTER column start_action_offset SET DEFAULT 0,
ALTER column  end_frequency_interval  SET DEFAULT 0;
