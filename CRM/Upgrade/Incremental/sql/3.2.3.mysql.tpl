SELECT @domainID        := min(id) FROM civicrm_domain;

-- CRM-6694, CRM-6716
SELECT @navid := id FROM civicrm_navigation WHERE name='Option Lists';
SELECT @wt := max(weight) FROM civicrm_navigation WHERE parent_id=@navid;
INSERT INTO civicrm_navigation
 ( domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
 ( @domainID, '{ts escape="sql"}Home{/ts}', 'Home', 'civicrm/dashboard&reset=1', NULL, '', NULL, 1, NULL, 0),
 ( @domainID, '{ts escape="sql"}Website Types{/ts}', 'Website Types', 'civicrm/admin/options/website_type&group=website_type&reset=1', 'administer CiviCRM', '', @navid, 1, NULL, @wt + 1);
 
 -- CRM-6726
 UPDATE  civicrm_option_value SET  filter =  0 WHERE  civicrm_option_value.name = 'Print PDF Letter';

--CRM-6655
 UPDATE civicrm_report_instance SET form_values = '{literal}a:37:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:25:"application_received_date";s:1:"1";}s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:12:"gender_id_op";s:2:"in";s:15:"gender_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"grant_type_op";s:2:"in";s:16:"grant_type_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:18:"amount_granted_min";s:0:"";s:18:"amount_granted_max";s:0:"";s:17:"amount_granted_op";s:3:"lte";s:20:"amount_granted_value";s:0:"";s:20:"amount_requested_min";s:0:"";s:20:"amount_requested_max";s:0:"";s:19:"amount_requested_op";s:3:"lte";s:22:"amount_requested_value";s:0:"";s:34:"application_received_date_relative";s:1:"0";s:30:"application_received_date_from";s:0:"";s:28:"application_received_date_to";s:0:"";s:28:"money_transfer_date_relative";s:1:"0";s:24:"money_transfer_date_from";s:0:"";s:22:"money_transfer_date_to";s:0:"";s:23:"grant_due_date_relative";s:1:"0";s:19:"grant_due_date_from";s:0:"";s:17:"grant_due_date_to";s:0:"";s:11:"description";s:12:"Grant Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviGrant";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}'  WHERE  report_id = 'grant';

-- CRM-6663
ALTER TABLE `civicrm_pledge_payment` 
      ADD `actual_amount` decimal(20,2) DEFAULT NULL COMMENT 'Actual amount that is paid as the Pledged installment amount.' AFTER `scheduled_amount`;
UPDATE `civicrm_pledge_payment` SET actual_amount = scheduled_amount WHERE contribution_id IS NOT NULL;

ALTER TABLE `civicrm_pledge` 
      ADD `original_installment_amount` decimal(20,2) NOT NULL COMMENT 'Original amount for each of the installments.' AFTER `amount`;
UPDATE `civicrm_pledge` SET `original_installment_amount` = `amount` / `installments`;

--CRM-6757
UPDATE `civicrm_option_value` 
 SET   {localize field='label'}label = name{/localize}
 WHERE  name IN ('day','month','week','year');
