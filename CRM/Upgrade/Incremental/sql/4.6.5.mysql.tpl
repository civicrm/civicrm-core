{* file to handle db changes in 4.6.5 during upgrade *}
-- CRM-16417 add failed payment activity type
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @option_group_id_act_wt := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @option_group_id_act_val := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';

INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_act, {localize}'{ts escape="sql"}Failed Payment{/ts}'{/localize}, @option_group_id_act_val+1,
'Failed Payment', NULL, 1, NULL, @option_group_id_act_wt+1, {localize}'{ts escape="sql"}Failed payment.{/ts}'{/localize}, 0, 1, 1, @contributeCompId, NULL);
