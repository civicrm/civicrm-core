{* file to handle db changes in 4.3.5 during upgrade*}
{include file='../CRM/Upgrade/4.3.5.msg_template/civicrm_msg_template.tpl'}
-- CRM-12799
DROP TABLE IF EXISTS civicrm_payment;