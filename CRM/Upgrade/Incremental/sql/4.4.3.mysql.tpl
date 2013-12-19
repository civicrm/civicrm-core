{* file to handle db changes in 4.4.3 during upgrade *}
{include file='../CRM/Upgrade/4.4.3.msg_template/civicrm_msg_template.tpl'}

-- CRM-13420
UPDATE civicrm_option_value
INNER JOIN civicrm_option_group ON civicrm_option_value.option_group_id = civicrm_option_group.id
SET civicrm_option_value.is_default  = 1
WHERE civicrm_option_group.name = 'payment_instrument' AND civicrm_option_value.name = 'Check';