{* file to handle db changes in 4.6.beta4 during upgrade *}
-- CRM-14792
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @option_group_id_act_wt := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @option_group_id_act_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;

INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_act, {localize}'{ts escape="sql"}Contact Merged{/ts}'{/localize}, @option_group_id_act_val+1, 'Contact Merged', NULL, 1, NULL, @option_group_id_act_wt+1, {localize}'{ts escape="sql"}Contact Merged{/ts}'{/localize}, 0, 1, 1, NULL, NULL);
