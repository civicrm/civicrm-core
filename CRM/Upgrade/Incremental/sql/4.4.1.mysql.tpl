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
