-- CRM-6458
SELECT @domainID := min(id) FROM civicrm_domain;
SELECT @reportlastID := id FROM civicrm_navigation where name = 'Reports';
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @nav_max_weight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;

INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,{localize field='description'} `description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
VALUES
   (@option_group_id_report, {localize}'{ts escape="sql"}Bookkeeping Transactions Report{/ts}'{/localize}, 'contribute/bookkeeping', 'CRM_Report_Form_Contribute_Bookkeeping', NULL, 0, 0, 29, {localize}'{ts escape="sql"}Shows Bookkeeping Transactions Report{/ts}'{/localize}, 0, 0, 1, 2, NULL);

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Bookkeeping Transactions Report', 'contribute/bookkeeping', 'Shows Bookkeeping Transactions Report', 'access CiviContribute', '{literal}a:26:{s:6:"fields";a:10:{s:12:"display_name";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:7:"trxn_id";s:1:"1";s:10:"invoice_id";s:1:"1";s:12:"check_number";s:1:"1";s:21:"payment_instrument_id";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:2:"id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:11:"description";s:37:"Shows Bookkeeping Transactions Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:1:"0";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;} {/literal}');
 
SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Bookkeeping Transactions Report{/ts}', '{literal}Bookkeeping Transactions Report{/literal}', 'access CiviContribute', '', @reportlastID, '1', NULL, @nav_max_weight+1 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID; 