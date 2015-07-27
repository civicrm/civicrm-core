{* file to handle db changes in 4.3.5 during upgrade*}
{include file='../CRM/Upgrade/4.3.5.msg_template/civicrm_msg_template.tpl'}
-- CRM-12799
DROP TABLE IF EXISTS civicrm_payment;

-- CRM-12929

INSERT INTO civicrm_setting
(domain_id, contact_id, is_domain, group_name, name, value)
VALUES
({$domainID}, NULL, 1, 'CiviCRM Preferences', 'allowPermDeleteFinancial', '{serialize}0{/serialize}');
