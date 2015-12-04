{* file to handle db changes in 4.7.beta2 during upgrade *}

-- Add payments user dashboard option

SELECT @option_group_id_dash := MAX(id) from civicrm_option_group where name = 'user_dashboard_options';
SELECT @option_group_id_dash_wt := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_dash;
SELECT @option_group_id_dash_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_dash;

INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_dash, {localize}'{ts escape="sql"}Payments{/ts}'{/localize}, @option_group_id_dash_val+1, 'Payments', NULL, NULL, NULL, @option_group_id_dash_wt+1, 0, 0, 1, NULL, NULL);

-- Add flag for existing payments

UPDATE civicrm_financial_trxn ft INNER JOIN
  (SELECT financial_account_id FROM civicrm_entity_financial_account efa INNER JOIN civicrm_option_value v ON efa.account_relationship = v.value AND v.name = 'Asset Account is'
   INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'account_relationship' GROUP BY financial_account_id)
   AS asset_fa ON ft.to_financial_account_id = asset_fa.financial_account_id SET ft.is_payment = TRUE;
