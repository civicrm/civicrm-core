-- CiviCRM v3.3.3
{include file='../CRM/Upgrade/3.3.3.msg_template/civicrm_msg_template.tpl'}

-- CRM-7172
UPDATE  `civicrm_navigation` SET  `permission` =  'access CiviMail,create mailings', `permission_operator` =  'OR' WHERE  name = 'New Mailing';