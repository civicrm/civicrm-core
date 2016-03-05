{* file to handle db changes in 4.7.alpha2 during upgrade *}

-- CRM-17221
UPDATE civicrm_state_province SET name = 'Phuket' WHERE name = 'Phaket';

-- Modify Instance for Soft Credit Reports - CRM-17169
UPDATE civicrm_report_instance
SET form_values = '{literal}a:23:{s:6:"fields";a:5:{s:21:"display_name_creditor";s:1:"1";s:24:"display_name_constituent";s:1:"1";s:14:"email_creditor";s:1:"1";s:14:"phone_creditor";s:1:"1";s:6:"amount";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:10:"amount_min";s:0:"";s:10:"amount_max";s:0:"";s:9:"amount_op";s:3:"lte";s:12:"amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:20:"Soft Credit details.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}'
WHERE report_id = 'contribute/softcredit';

-- CRM-17302
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @CompId := MAX(id) FROM civicrm_component where name = 'CiviContribute';

UPDATE civicrm_option_value 
SET component_id = @CompId 
WHERE (name = 'Cancel Recurring Contribution' OR name = 'Update Recurring Contribution Billing Details' OR name = 'Update Recurring Contribution') AND option_group_id = @option_group_id_act;

-- CRM-17184
INSERT INTO `civicrm_state_province`
(`name`, `abbreviation`, `country_id`)
VALUES
('La Libertad', 'LI', 1066),
('La Paz', 'PA', 1066),
('La Union','UN', 1066);