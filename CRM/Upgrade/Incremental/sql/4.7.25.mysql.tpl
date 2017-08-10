{* file to handle db changes in 4.7.25 during upgrade *}

ALTER TABLE `civicrm_menu`
ADD COLUMN `module_data` text    COMMENT 'All other menu metadata not stored in other fields';
