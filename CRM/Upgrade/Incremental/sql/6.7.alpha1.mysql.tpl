{* file to handle db changes in 6.7.alpha1 during upgrade *}

{* Default to NULL *}
ALTER TABLE `civicrm_translation` MODIFY COLUMN `entity_table` varchar(64) NULL COMMENT 'Table where referenced item is stored';
ALTER TABLE `civicrm_translation` MODIFY COLUMN `entity_field` varchar(64) NULL COMMENT 'Table where referenced item is stored';
ALTER TABLE `civicrm_translation` MODIFY COLUMN `entity_id` int(11) NULL COMMENT 'Table where referenced item is stored';
