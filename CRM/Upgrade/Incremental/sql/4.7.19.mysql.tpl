{* file to handle db changes in 4.7.19 during upgrade *}
-- CRM-19715
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @close_acc_period_act_val := `value` FROM civicrm_option_value WHERE option_group_id = @option_group_id_act AND name = 'Close Accounting Period';
SELECT @close_accounting_period_activity_count := count(id) FROM `civicrm_activity` WHERE `activity_type_id` = @close_acc_period_act_val;

-- Delete Close Accounting Period activity type
DELETE FROM civicrm_option_value 
    WHERE option_group_id = @option_group_id_act AND name = 'Close Accounting Period' AND @close_accounting_period_activity_count = 0;
