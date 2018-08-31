{* file to handle db changes in 5.6.alpha1 during upgrade *}

ALTER TABLE civicrm_prevnext_cache
  CHANGE `entity_id2` `entity_id2` int unsigned NULL   COMMENT 'FK to entity table specified in entity_table column.';

UPDATE civicrm_action_schedule SET start_action_date = 'start_date' WHERE start_action_date = 'event_start_date';
UPDATE civicrm_action_schedule SET start_action_date = 'end_date' WHERE start_action_date = 'event_end_date';
UPDATE civicrm_action_schedule SET start_action_date = 'join_date' WHERE start_action_date = 'membership_join_date';
UPDATE civicrm_action_schedule SET start_action_date = 'end_date' WHERE start_action_date = 'membership_end_date';
