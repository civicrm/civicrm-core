{* file to handle db changes in 4.7.12 during upgrade *}

-- CRM-19271
ALTER TABLE civicrm_action_schedule CHANGE start_action_condition start_action_conditionx VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Reminder Action';