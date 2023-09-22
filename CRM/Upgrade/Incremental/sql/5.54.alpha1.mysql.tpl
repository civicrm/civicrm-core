{* file to handle db changes in 5.54.alpha1 during upgrade *}
ALTER TABLE `civicrm_mailing_bounce_type` CHANGE `name` `name` VARCHAR(255) NOT NULL COMMENT 'Type of bounce', CHANGE `description` `description` VARCHAR(2048) NULL DEFAULT NULL COMMENT 'A description of this bounce type';

SELECT @og_recent_items_providers := max(id) from civicrm_option_group where name = 'recent_items_providers';

{* Ensure all recent_items_providers option values are reserved *}
UPDATE `civicrm_option_value`
SET `is_reserved` = 1
WHERE `option_group_id` = @og_recent_items_providers;

{* Fix option values accidentally created with numeric values in the 5.53.alpha1 upgrade *}
UPDATE `civicrm_option_value`
SET `value` = `name`
WHERE `option_group_id` = @og_recent_items_providers AND `value` REGEXP '^[0-9]+$';

{* Fix option values created with wrong name by the 5.53.0 installer *}
UPDATE `civicrm_option_value`
SET `name` = `value`
WHERE `option_group_id` = @og_recent_items_providers;
