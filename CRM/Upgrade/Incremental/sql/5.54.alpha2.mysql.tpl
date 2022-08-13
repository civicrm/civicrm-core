{* file to handle db changes in 5.54.alpha2 during upgrade *}

ALTER TABLE `civicrm_dashboard_contact` MODIFY COLUMN `is_active` TINYINT(4) DEFAULT 0 COMMENT 'Is this widget active?';
