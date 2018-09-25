#fix typo when setting default in https://github.com/civicrm/civicrm-core/pull/12410
ALTER TABLE civicrm_option_group MODIFY COLUMN  is_locked TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'A lock to remove the ability to add new options via the UI.';

UPDATE civicrm_option_group
SET is_locked = 0
WHERE name REGEXP '.*_2018[0-9]+$'
AND id IN (SELECT DISTINCT option_group_id FROM civicrm_custom_field);
