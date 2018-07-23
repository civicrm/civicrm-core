{* file to handle db changes in 5.5.alpha1 during upgrade *}
#https://lab.civicrm.org/dev/core/issues/228
UPDATE civicrm_option_group SET is_active = 0 WHERE is_active IS NULL;
ALTER TABLE civicrm_option_group MODIFY COLUMN is_active TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'Is this option group active?';
UPDATE civicrm_option_group SET is_locked = 0 WHERE is_locked IS NULL;
ALTER TABLE civicrm_option_group MODIFY COLUMN  is_locked TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'A lock to remove the ability to add new options via the UI.';
#is_reserved already has a default so is effectively required but let's be explicit.
UPDATE civicrm_option_group SET `is_reserved` = 0 WHERE `is_reserved` IS NULL;
ALTER TABLE civicrm_option_group MODIFY COLUMN `is_reserved` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Is this a predefined system option group (i.e. it can not be deleted)?';

#https://lab.civicrm.org/dev/core/issues/155
{* Fix is_reserved flag on civicrm_option_group table *}
UPDATE civicrm_option_group AS cog INNER JOIN civicrm_custom_field AS ccf
ON cog.id = ccf.option_group_id
SET cog.is_reserved = 0 WHERE cog.is_active = 1 AND ccf.is_active = 1;
UPDATE civicrm_option_group SET is_reserved = 1 WHERE name='environment';

UPDATE civicrm_navigation SET url = 'civicrm/admin/options?action=browse&reset=1' WHERE name = 'Dropdown Options' AND domain_id = {$domainID};
