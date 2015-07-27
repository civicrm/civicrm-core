-- db template customization

CREATE TABLE IF NOT EXISTS civicrm_persistent (
     id int(10) unsigned NOT NULL auto_increment COMMENT 'Persistent Record Id',
     context varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Context for which name data pair is to be stored',
     name varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'Name of Context',
     data longtext collate utf8_unicode_ci COMMENT 'data associated with name',
     is_config tinyint(4) NOT NULL default '0' COMMENT 'Config Settings',
     PRIMARY KEY  (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4;

--CRM-6655
SELECT @domainID        := min(id) FROM civicrm_domain;
SELECT @reportlastID    := id FROM civicrm_navigation where name = 'Reports';
SELECT @ogrID           := max(id) from civicrm_option_group where name = 'report_template';
SELECT @nav_max_weight  := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;
SELECT @grantCompId     := max(id) FROM civicrm_component where name = 'CiviGrant';
SELECT @eventCompId     := max(id) FROM civicrm_component where name = 'CiviEvent';
SELECT @max_weight      := MAX(ROUND(weight)) from civicrm_option_value WHERE option_group_id = @ogrID;

INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight,{localize field='description'} description{/localize}, is_optgroup,is_reserved, is_active, component_id, visibility_id )
VALUES
    (@ogrID  , {localize}'{ts escape="sql"}Grant Report{/ts}'{/localize}, 'grant', 'CRM_Report_Form_Grant', NULL, 0, 0,  @max_weight+1, {localize}'{ts escape="sql"}Grant Report{/ts}'{/localize}, 0, 0, 1, @grantCompId, NULL),
    (@ogrID, {localize}'{ts escape="sql"}Participant list Count Report{/ts}'{/localize}, 'event/participantlist', 'CRM_Report_Form_Event_ParticipantListCount', NULL, 0, 0, @max_weight+2, {localize}'{ts escape="sql"}Shows the Participant list with Participant Count.{/ts}'{/localize}, 0, 0, 1, @eventCompId, NULL),
    (@ogrID, {localize}'{ts escape="sql"}Income Count Summary Report{/ts}'{/localize}, 'event/incomesummary', 'CRM_Report_Form_Event_IncomeCountSummary', NULL, 0, 0, @max_weight+3, {localize}'{ts escape="sql"}Shows the Income Summary of events with Count.{/ts}'{/localize}, 0, 0, 1, @eventCompId, NULL);

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, 'Grant Report', 'grant', 'Grant Report', 'access CiviGrant', '{literal}a:30:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:25:"application_received_date";s:1:"1";}s:15:"display_name_op";s:3:"has";s:18:"display_name_value";s:0:"";s:13:"grant_type_op";s:2:"in";s:16:"grant_type_value";a:0:{}s:15:"grant_status_op";s:2:"in";s:18:"grant_status_value";a:0:{}s:18:"amount_granted_min";s:0:"";s:18:"amount_granted_max";s:0:"";s:17:"amount_granted_op";s:3:"lte";s:20:"amount_granted_value";s:0:"";s:20:"amount_requested_min";s:0:"";s:20:"amount_requested_max";s:0:"";s:19:"amount_requested_op";s:3:"lte";s:22:"amount_requested_value";s:0:"";s:34:"application_received_date_relative";s:1:"0";s:30:"application_received_date_from";s:0:"";s:28:"application_received_date_to";s:0:"";s:28:"money_transfer_date_relative";s:1:"0";s:24:"money_transfer_date_from";s:0:"";s:22:"money_transfer_date_to";s:0:"";s:23:"grant_due_date_relative";s:1:"0";s:19:"grant_due_date_from";s:0:"";s:17:"grant_due_date_to";s:0:"";s:11:"description";s:12:"Grant Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviGrant";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Grant Report{/ts}', '{literal}Grant Report{/literal}', 'access CiviGrant', '',@reportlastID, '1', NULL, @nav_max_weight+1 );

UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;
