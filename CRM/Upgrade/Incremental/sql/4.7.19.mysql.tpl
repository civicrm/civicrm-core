{* file to handle db changes in 4.7.19 during upgrade *}
-- CRM-19715
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @close_acc_period_act_val := `value` FROM civicrm_option_value WHERE option_group_id = @option_group_id_act AND name = 'Close Accounting Period';
SELECT @close_accounting_period_activity_count := count(id) FROM `civicrm_activity` WHERE `activity_type_id` = @close_acc_period_act_val;

-- Delete Close Accounting Period activity type
DELETE FROM civicrm_option_value 
    WHERE option_group_id = @option_group_id_act AND name = 'Close Accounting Period' AND @close_accounting_period_activity_count = 0;

--  CRM-19517 Disable all price fields and price field options that use disabled fianancial types
UPDATE civicrm_price_field_value cpfv
INNER JOIN civicrm_financial_type cft ON cft.id = cpfv.financial_type_id
SET cpfv.is_active = 0
WHERE cft.is_active = 0;

UPDATE civicrm_price_field cpf
LEFT JOIN (SELECT DISTINCT price_field_id AS price_field_id
  FROM civicrm_price_field_value
  WHERE is_active = 1) AS price_field
ON price_field.price_field_id = cpf.id
SET cpf.is_active = 0
WHERE price_field.price_field_id IS NULL;

-- CRM-20400
{include file='../CRM/Upgrade/4.7.19.msg_template/civicrm_msg_template.tpl'}
