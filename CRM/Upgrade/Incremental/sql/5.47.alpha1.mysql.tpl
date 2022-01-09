{* file to handle db changes in 5.47.alpha1 during upgrade *}

UPDATE `civicrm_navigation` SET `is_active` = 0 WHERE `is_active` IS NULL;
UPDATE `civicrm_navigation` SET `weight` = 0 WHERE `weight` IS NULL;
ALTER TABLE `civicrm_navigation`
  MODIFY COLUMN `is_active` tinyint NOT NULL DEFAULT 1 COMMENT 'Is this navigation item active?',
  MODIFY COLUMN `weight` int NOT NULL DEFAULT 0 COMMENT 'Ordering of the navigation items in various blocks.';
