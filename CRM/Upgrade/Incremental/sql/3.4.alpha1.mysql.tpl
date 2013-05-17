-- CRM-7346
ALTER TABLE `civicrm_campaign` ADD `goal_general` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci default NULL NULL COMMENT 'General goals for Campaign.';
ALTER TABLE `civicrm_campaign` ADD `goal_revenue` DECIMAL( 20, 2 ) default NULL NULL COMMENT 'The target revenue for this campaign.';

-- CRM-7345
ALTER TABLE `civicrm_custom_group` CHANGE `extends` `extends` ENUM( 'Contact', 'Individual', 'Household', 'Organization', 'Location', 'Address', 'Contribution', 'Activity', 'Relationship', 'Group', 'Membership', 'Participant', 'Event', 'Grant', 'Pledge', 'Case', 'Campaign' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'Contact' COMMENT 'Type of object this group extends (can add other options later e.g. contact_address, etc.).';

-- CRM-7362
ALTER TABLE `civicrm_contribution`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this contribution has been triggered.',
ADD CONSTRAINT FK_civicrm_contribution_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_contribution_page`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which we are collecting contributions with this page.',
ADD CONSTRAINT FK_civicrm_contribution_page_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_membership`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this membership is attached.',
ADD CONSTRAINT FK_civicrm_membership_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_pledge`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this pledge has been initiated.',
ADD CONSTRAINT FK_civicrm_pledge_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_activity`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this activity has been triggered.',
ADD CONSTRAINT FK_civicrm_activity_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_participant`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this participant has been registered.',
ADD CONSTRAINT FK_civicrm_participant_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_event`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this event has been created.',
ADD CONSTRAINT FK_civicrm_event_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_mailing`
ADD `campaign_id` int(10) unsigned default NULL COMMENT 'The campaign for which this mailing has been initiated.',
ADD CONSTRAINT FK_civicrm_mailing_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL,
ADD `domain_id` int(10) unsigned default NULL COMMENT 'Which site is this mailing for.' AFTER id,
ADD CONSTRAINT FK_civicrm_mailing_domain_id FOREIGN KEY (domain_id) REFERENCES civicrm_domain(id) ON DELETE SET NULL;

-- done w/ CRM-7345

-- CRM-7223
CREATE TABLE civicrm_mailing_recipients (
     id int unsigned NOT NULL AUTO_INCREMENT  ,
     mailing_id int unsigned NOT NULL   COMMENT 'The ID of the mailing this Job will send.',
     contact_id int unsigned NOT NULL   COMMENT 'FK to Contact',
     email_id int unsigned NOT NULL   COMMENT 'FK to Email',
     PRIMARY KEY ( id ),
     CONSTRAINT FK_civicrm_mailing_recipients_mailing_id FOREIGN KEY (mailing_id) REFERENCES civicrm_mailing(id) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_mailing_recipients_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_mailing_recipients_email_id FOREIGN KEY (email_id) REFERENCES civicrm_email(id) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;


-- CRM-7352 add logging report templates
SELECT @option_group_id_report := MAX(id)     FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @contributeCompId       := MAX(id)     FROM civicrm_component where name = 'CiviContribute';
INSERT INTO civicrm_option_value
  (option_group_id,         {localize field='label'}label{/localize},                   value,                        name,                                        weight,                 {localize field='description'}description{/localize},                                              is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Contribute Logging Report (Summary)'{/localize}, 'logging/contribute/summary', 'CRM_Report_Form_Contribute_LoggingSummary', @weight := @weight + 1, {localize}'Contribution modification report for the logging infrastructure (summary).'{/localize}, 0,         @contributeCompId),
  (@option_group_id_report, {localize}'Contribute Logging Report (Detail)'{/localize},  'logging/contribute/detail',  'CRM_Report_Form_Contribute_LoggingDetail',  @weight := @weight + 1, {localize}'Contribute modification report for the logging infrastructure (detail).'{/localize},    0,         @contributeCompId);

-- CRM-7297 Membership Upsell
ALTER TABLE civicrm_membership_log ADD membership_type_id  INT UNSIGNED COMMENT 'FK to Membership Type.',
ADD CONSTRAINT FK_civicrm_membership_log_membership_type_id FOREIGN KEY (membership_type_id) REFERENCES civicrm_membership_type(id)
ON DELETE SET NULL;

UPDATE civicrm_membership_log cml INNER JOIN civicrm_membership cm
ON cml.membership_id=cm.id SET cml.membership_type_id=cm.membership_type_id;

-- CRM-7445 add client to case
SELECT @option_group_id_act            := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @value                 := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @caseCompId       := max(id) FROM civicrm_component where name = 'CiviCase';
INSERT INTO civicrm_option_value
  (option_group_id,         {localize field='label'}label{/localize},                   value,                        name,                                        weight,                 {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_act,   {localize}'Add Client To Case'{/localize},                   @value,                       'Add Client To Case',                         @weight,               {localize}NULL{/localize},                             1,         @caseCompId );

-- CRM-7317
CREATE TABLE civicrm_prevnext_cache (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  entity_table varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'physical tablename for entity being joined to discount, e.g. civicrm_event',
  entity_id1 int(10) unsigned NOT NULL COMMENT 'FK to entity table specified in entity_table column.',
  entity_id2 int(10) unsigned NOT NULL COMMENT 'FK to entity table specified in entity_table column.',
  cacheKey varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Unique path name for cache element of the searched item',
  data longtext COLLATE utf8_unicode_ci COMMENT 'cached snapshot of the serialized data',
  PRIMARY KEY ( id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- CRM-7489
ALTER TABLE `civicrm_tag`
ADD `created_date` DATETIME NULL DEFAULT NULL COMMENT 'Date and time that tag was created.',
ADD `created_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_contact, who created this tag',
ADD CONSTRAINT FK_civicrm_tag_created_id FOREIGN KEY (created_id) REFERENCES civicrm_contact(id) ON DELETE SET NULL;

-- CRM-7494
    UPDATE  civicrm_option_value value
INNER JOIN  civicrm_option_group grp ON ( grp.id = value.option_group_id )
       SET  value.name = 'CRM_Report_Form_Walklist_Walklist'
     WHERE  grp.name = 'report_template'
       AND  value.name = 'CRM_Report_Form_Walklist';

SELECT @reportlastID       := MAX(id) FROM civicrm_navigation where name = 'Reports';
SELECT @campaignCompId     := MAX(id) FROM civicrm_component where name = 'CiviCampaign';
SELECT @reportOptGrpId     := MAX(id) FROM civicrm_option_group WHERE name = 'report_template';
SELECT @reportOptValMaxWt  := MAX(ROUND(weight)) FROM civicrm_option_value WHERE option_group_id = @reportOptGrpId;
SELECT @nav_max_weight     := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;

INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight,{localize field='description'} description{/localize}, is_optgroup,is_reserved, is_active, component_id, visibility_id )
VALUES
    (@reportOptGrpId, {localize}'{ts escape="sql"}Walk List Survey Report{/ts}'{/localize}, 'survey/detail', 'CRM_Report_Form_Campaign_SurveyDetails', NULL, 0, 0,  @reportOptValMaxWt+1, {localize}'{ts escape="sql"}Provides a detailed report for your walk list survey{/ts}'{/localize}, 0, 0, 1, @campaignCompId, NULL );

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( {$domainID}, 'Walk List Survey Report', 'survey/detail', 'Provides a detailed report for your walk list survey', 'access CiviReport', '{literal}a:39:{s:6:"fields";a:3:{s:12:"display_name";s:1:"1";s:9:"survey_id";s:1:"1";s:6:"result";s:1:"1";}s:22:"assignee_contact_id_op";s:2:"eq";s:25:"assignee_contact_id_value";s:0:"";s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:12:"survey_id_op";s:2:"in";s:15:"survey_id_value";a:0:{}s:12:"status_id_op";s:2:"eq";s:15:"status_id_value";s:1:"1";s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:52:"Provides a detailed report for your walk list survey";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviReport";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Walk List Survey Report{/ts}', 'Walk List Survey Report', 'administer CiviCampaign,manage campaign,interview campaign contacts', 'OR', @reportlastID, '1', NULL, @nav_max_weight+1 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

