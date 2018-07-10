{* file to handle db changes in 5.5.alpha1 during upgrade *}
#https://lab.civicrm.org/dev/core/issues/228
UPDATE civicrm_option_group SET is_active = 0 WHERE is_active IS NULL;
ALTER TABLE civicrm_option_group MODIFY COLUMN is_active TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'Is this option group active?';
UPDATE civicrm_option_group SET is_locked = 0 WHERE is_locked IS NULL;
ALTER TABLE civicrm_option_group MODIFY COLUMN  is_locked TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'A lock to remove the ability to add new options via the UI.';
#is_reserved already has a default so is effectively required but let's be explicit.
UPDATE civicrm_option_group SET `is_reserved` = 0 WHERE `is_reserved` IS NULL;
ALTER TABLE civicrm_option_group MODIFY COLUMN `is_reserved` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Is this a predefined system option group (i.e. it can not be deleted)?';
