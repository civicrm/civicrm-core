-- CRM-7455
UPDATE civicrm_report_instance 
   SET title       = '{ts escape="sql"}Grant Report (Detail){/ts}', 
       report_id   = 'grant/detail', 
       description = '{ts escape="sql"}Grant Report Detail{/ts}',
       form_values = '{literal}a:37:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:25:"application_received_date";s:1:"1";}s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:12:"gender_id_op";s:2:"in";s:15:"gender_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"grant_type_op";s:2:"in";s:16:"grant_type_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:18:"amount_granted_min";s:0:"";s:18:"amount_granted_max";s:0:"";s:17:"amount_granted_op";s:3:"lte";s:20:"amount_granted_value";s:0:"";s:20:"amount_requested_min";s:0:"";s:20:"amount_requested_max";s:0:"";s:19:"amount_requested_op";s:3:"lte";s:22:"amount_requested_value";s:0:"";s:34:"application_received_date_relative";s:1:"0";s:30:"application_received_date_from";s:0:"";s:28:"application_received_date_to";s:0:"";s:28:"money_transfer_date_relative";s:1:"0";s:24:"money_transfer_date_from";s:0:"";s:22:"money_transfer_date_to";s:0:"";s:23:"grant_due_date_relative";s:1:"0";s:19:"grant_due_date_from";s:0:"";s:17:"grant_due_date_to";s:0:"";s:11:"description";s:19:"Grant Report Detail";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviGrant";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}'
 WHERE report_id   = 'grant';

{if $multilingual}
UPDATE civicrm_option_value
   SET value       = 'grant/detail', 
       name        = 'CRM_Report_Form_Grant_Detail'
 WHERE value       = 'grant'
   AND name        = 'CRM_Report_Form_Grant';
 
{foreach from=$locales item=loc}
UPDATE civicrm_option_value
   SET label_{$loc}       = '{ts escape="sql"}Grant Report (Detail){/ts}',
       description_{$loc} = '{ts escape="sql"}Grant Report Detail{/ts}'
 WHERE value              = 'grant/detail'
   AND name               = 'CRM_Report_Form_Grant_Detail';
 {/foreach}

{else}

UPDATE civicrm_option_value
   SET label       = '{ts escape="sql"}Grant Report (Detail){/ts}',
       value       = 'grant/detail', 
       name        = 'CRM_Report_Form_Grant_Detail',
       description = '{ts escape="sql"}Grant Report Detail{/ts}'
 WHERE value       = 'grant'
   AND name        = 'CRM_Report_Form_Grant';
{/if}

UPDATE civicrm_navigation
   SET label       = '{ts escape="sql"}Grant Report (Detail){/ts}',
        name       = '{ts escape="sql"}Grant Report (Detail){/ts}'
 WHERE  name       = '{ts escape="sql"}Grant Report{/ts}';


SELECT @domainID        := MIN(id) FROM civicrm_domain;
SELECT @reportlastID    := id FROM civicrm_navigation WHERE name = 'Reports';
SELECT @ogrID           := MAX(id) FROM civicrm_option_group WHERE name = 'report_template';
SELECT @nav_max_weight  := MAX(ROUND(weight)) FROM civicrm_navigation WHERE parent_id = @reportlastID;
SELECT @grantCompId     := MAX(id) FROM civicrm_component WHERE name = 'CiviGrant';
SELECT @max_weight      := MAX(ROUND(weight)) FROM civicrm_option_value WHERE option_group_id = @ogrID;

INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight,{localize field='description'} description{/localize}, is_optgroup,is_reserved, is_active, component_id, visibility_id ) 
VALUES
    (@ogrID  , {localize}'{ts escape="sql"}Grant Report (Statistics){/ts}'{/localize}, 'grant/statistics', 'CRM_Report_Form_Grant_Statistics', NULL, 0, 0,  @max_weight+1, {localize}'{ts escape="sql"}Shows statistics for Grants{/ts}'{/localize}, 0, 0, 1, @grantCompId, NULL);
 
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES 
    ( @domainID, 'Grant Report (Statistics)', 'grant/statistics', 'Shows statistics for Grants', 'access CiviGrant', '{literal}a:45:{s:6:"fields";a:3:{s:18:"summary_statistics";s:1:"1";s:9:"gender_id";s:1:"1";s:12:"contact_type";s:1:"1";}s:34:"application_received_date_relative";s:1:"0";s:30:"application_received_date_from";s:0:"";s:28:"application_received_date_to";s:0:"";s:22:"decision_date_relative";s:1:"0";s:18:"decision_date_from";s:0:"";s:16:"decision_date_to";s:0:"";s:28:"money_transfer_date_relative";s:1:"0";s:24:"money_transfer_date_from";s:0:"";s:22:"money_transfer_date_to";s:0:"";s:23:"grant_due_date_relative";s:1:"0";s:19:"grant_due_date_from";s:0:"";s:17:"grant_due_date_to";s:0:"";s:13:"grant_type_op";s:2:"in";s:16:"grant_type_value";a:1:{i:0;s:1:"1";}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:20:"amount_requested_min";s:0:"";s:20:"amount_requested_max";s:0:"";s:19:"amount_requested_op";s:3:"lte";s:22:"amount_requested_value";s:0:"";s:18:"amount_granted_min";s:0:"";s:18:"amount_granted_max";s:0:"";s:17:"amount_granted_op";s:3:"lte";s:20:"amount_granted_value";s:0:"";s:24:"grant_report_received_op";s:2:"eq";s:27:"grant_report_received_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:12:"region_id_op";s:2:"in";s:15:"region_id_value";a:0:{}s:11:"custom_7_op";s:2:"in";s:14:"custom_7_value";a:0:{}s:11:"custom_9_op";s:3:"has";s:14:"custom_9_value";s:0:"";s:11:"custom_8_op";s:4:"mhas";s:14:"custom_8_value";a:0:{}s:11:"description";s:28:"Shows statistics for Grants.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviGrant";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Grant Report (Statistics){/ts}', '{ts}Grant Report (Statistics){/ts}', 'access CiviGrant', '',@reportlastID, '1', NULL, @nav_max_weight+1 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;
