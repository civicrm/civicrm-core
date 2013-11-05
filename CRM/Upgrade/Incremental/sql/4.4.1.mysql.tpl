{* file to handle db changes in 4.4.1 during upgrade *}
-- CRM-13327
SELECT @option_group_id_name_badge := max(id) from civicrm_option_group where name = 'name_badge';
UPDATE civicrm_option_value
SET value = '{literal}{"name":"Avery 5395","paper-size":"a4","metric":"mm","lMargin":15,"tMargin":26,"NX":2,"NY":4,"SpaceX":10,"SpaceY":5,"width":83,"height":57,"font-size":12,"orientation":"portrait","font-name":"helvetica","font-style":"","lPadding":3,"tPadding":3}{/literal}'
WHERE option_group_id = @option_group_id_name_badge AND name = 'Avery 5395';

-- CRM-13669
{literal}
UPDATE civicrm_option_value SET name = 'Dear {contact.household_name}'
WHERE name = 'Dear {contact.househols_name}';
{/literal}

-- CRM-13698
SELECT @option_group_id_acs := max(id) from civicrm_option_group where name = 'activity_status';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_acs;
SELECT @max_wt     := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_acs;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_acs, {localize}'{ts escape="sql"}Available{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'Available',  NULL, 0, NULL, (SELECT @max_wt := @max_wt+1), 0, 0, 1, NULL, NULL),
  (@option_group_id_acs, {localize}'{ts escape="sql"}No-show{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'No-show',  NULL, 0, NULL, (SELECT @max_wt := @max_wt+1), 0, 0, 1, NULL, NULL);