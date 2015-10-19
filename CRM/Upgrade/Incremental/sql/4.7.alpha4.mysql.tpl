{* file to handle db changes in 4.7.alpha4 during upgrade *}

-- CRM-16901
SELECT @option_group_id_cvOpt := max(id) FROM civicrm_option_group WHERE name = 'contact_view_options';
SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op  WHERE op.option_group_id  = @option_group_id_cvOpt;
SELECT @max_wt := ROUND(val.weight) FROM civicrm_option_value val WHERE val.option_group_id = @option_group_id_cvOpt AND val.name = 'CiviContribute';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_cvOpt, {localize}'{ts escape="sql"}Recurring Contribution{/ts}'{/localize}, @max_val+1, 'CiviContributeRecur', NULL, 0, NULL,  @max_wt+1, 0, 0, 1, NULL, NULL);
