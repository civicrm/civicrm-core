{* file to handle db changes in 4.3.5 during upgrade*}
{include file='../CRM/Upgrade/4.3.5.msg_template/civicrm_msg_template.tpl'}
-- CRM-12799
DROP TABLE IF EXISTS civicrm_payment;

-- CRM-12929

INSERT INTO civicrm_setting
(domain_id, contact_id, is_domain, group_name, name, value)
VALUES
({$domainID}, NULL, 1, 'CiviCRM Preferences', 'allowPermDeleteFinancial', '{serialize}0{/serialize}');

-- CRM-12844
-- DELETE bad data
DELETE cli FROM `civicrm_contribution` cc
LEFT JOIN civicrm_line_item cli ON cli.entity_id = cc.id 
LEFT JOIN civicrm_financial_item cfi ON cfi.entity_id = cli.id AND cfi.entity_table = 'civicrm_line_item' 
LEFT JOIN civicrm_price_field cpf ON cpf.id = cli.price_field_id
LEFT JOIN civicrm_price_set cps ON cps.id = cpf.price_set_id

WHERE cc.contribution_recur_id IS NOT NULL 
AND cli.entity_table = 'civicrm_contribution' AND cfi.id IS NULL
AND cps.is_quick_config = 1;
