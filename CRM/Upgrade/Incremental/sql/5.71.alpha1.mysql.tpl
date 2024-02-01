{* file to handle db changes in 5.71.alpha1 during upgrade *}

-- Add fields and indexes to civicrm_custom_field
ALTER TABLE `civicrm_custom_field`
ADD `fk_entity_on_delete` VARCHAR(255) NOT NULL DEFAULT 'set_null' COMMENT 'Behavior if referenced entity is deleted.' AFTER `fk_entity`,
