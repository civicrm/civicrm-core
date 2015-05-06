{* file to handle db changes in 4.6.3 during upgrade *}
-- CRM-16307 fix CRM-15578 typo - Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CiviMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests';

--CRM-16320
{include file='../CRM/Upgrade/4.6.3.msg_template/civicrm_msg_template.tpl'}

--CRM-16391 and CRM-16392
UPDATE civicrm_uf_field
SET {localize field="label"}label = '{ts escape="sql"}Financial Type{/ts}'{/localize}
WHERE field_type = 'Contribution' AND field_name='financial_type';

UPDATE civicrm_uf_field
SET {localize field="label"}label = '{ts escape="sql"}Membership Type{/ts}'{/localize}
WHERE field_type = 'Membership' AND field_name='membership_type';
