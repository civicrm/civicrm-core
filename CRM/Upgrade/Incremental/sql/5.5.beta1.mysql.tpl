{* file to handle db changes in 5.5.beta1 during upgrade *}

UPDATE civicrm_action_schedule SET start_action_date = 'start_date' WHERE start_action_date = 'event_start_date';
UPDATE civicrm_action_schedule SET start_action_date = 'end_date' WHERE start_action_date = 'event_end_date';
UPDATE civicrm_action_schedule SET start_action_date = 'join_date' WHERE start_action_date = 'membership_join_date';
UPDATE civicrm_action_schedule SET start_action_date = 'end_date' WHERE start_action_date = 'membership_end_date';
