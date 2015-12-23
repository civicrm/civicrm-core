{* file to handle db changes in 4.8.alpha1 during upgrade *}

-- CRM-17745: Make maximum additional participants configurable
ALTER TABLE civicrm_event
  ADD COLUMN max_additional_participants int(10) unsigned
  DEFAULT 0
  COMMENT 'Maximum number of additional participants that can be registered on a single booking'
  AFTER is_multiple_registrations;
UPDATE civicrm_event
  SET max_additional_participants = 9
  WHERE is_multiple_registrations = 1;