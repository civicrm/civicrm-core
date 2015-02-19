{* file to handle db changes in 4.5.6 during upgrade *}
   
-- CRM-15760
ALTER TABLE `civicrm_action_schedule` CHANGE `entity_value` `entity_value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Entity value';