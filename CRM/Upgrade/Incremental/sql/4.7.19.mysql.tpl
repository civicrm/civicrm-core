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
INNER JOIN civicrm_price_field pf ON pf.id = cpfv.price_field_id
INNER JOIN civicrm_price_set ps ON ps.id = pf.price_set_id
SET cpfv.is_active = 0
WHERE cft.is_active = 0 AND ps.is_quick_config = 0;

UPDATE civicrm_price_field cpf
LEFT JOIN (SELECT DISTINCT price_field_id AS price_field_id
  FROM civicrm_price_field_value
  WHERE is_active = 1) AS price_field
ON price_field.price_field_id = cpf.id
SET cpf.is_active = 0
WHERE price_field.price_field_id IS NULL;

-- CRM-20400
{include file='../CRM/Upgrade/4.7.19.msg_template/civicrm_msg_template.tpl'}

-- CRM-20402 Improve dectection of spam bounces
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Spam';
UPDATE civicrm_mailing_bounce_pattern SET pattern = '(detected|rejected) (as|due to) spam' WHERE bounce_type_id = @bounceTypeID AND pattern = '(detected|rejected) as spam';

-- CRM-19464 add 'Supplemental Address 3', increment weights after supplemental_address_2 to slot in this new one
SELECT @option_group_id_adOpt := max(id) from civicrm_option_group where name = 'address_options';
SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id = @option_group_id_adOpt;
SELECT @supp2_wt := weight FROM civicrm_option_value WHERE name = 'supplemental_address_2';
UPDATE civicrm_option_value SET weight = weight + 1 WHERE option_group_id = @option_group_id_adOpt AND weight > @supp2_wt;
INSERT INTO
 `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}description{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
VALUES
  (@option_group_id_adOpt, {localize}'{ts escape="sql"}Supplemental Address 3{/ts}'{/localize}, (SELECT @max_val := @max_val + 1), 'supplemental_address_3', NULL, 0, NULL, (SELECT @supp2_wt := @supp2_wt + 1), {localize}''{/localize}, 0, 0, 1, NULL, NULL, NULL);

-- Some legacy sites have `0000-00-00 00:00:00` values in
-- `civicrm_financial_trxn.trxn_date` which correspond to the same value in
-- `civicrm_contribution.receive_date`
UPDATE civicrm_financial_trxn SET trxn_date = NULL WHERE trxn_date = '0000-00-00 00:00:00';
UPDATE civicrm_contribution SET receive_date = NULL WHERE receive_date = '0000-00-00 00:00:00';

-- CRM-20439 rename card_type to card_type_id of civicrm_financial_trxn table (IIDA-126)
ALTER TABLE `civicrm_financial_trxn` CHANGE `card_type` `card_type_id` INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'FK to accept_creditcard option group values';

-- CRM-20465
ALTER TABLE `civicrm_financial_trxn` CHANGE `pan_truncation` `pan_truncation` VARCHAR( 4 ) NULL DEFAULT NULL COMMENT 'Last 4 digits of credit card';
