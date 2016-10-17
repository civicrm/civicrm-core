{* file to handle db changes in 4.7.12 during upgrade *}

-- CRM-19271
ALTER TABLE civicrm_action_schedule CHANGE start_action_condition start_action_condition VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Reminder Action';

-- CRM-19367
SELECT @option_group_id_fat := max(id) from civicrm_option_group where name = 'financial_account_type';

UPDATE civicrm_option_value SET {localize field="description"}description = 'Things you owe, like a grant still to be disbursed'{/localize}
  WHERE name = 'Liability' AND option_group_id = @option_group_id_fat;