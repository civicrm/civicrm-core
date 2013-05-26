{include file='../CRM/Upgrade/4.1.alpha1.msg_template/civicrm_msg_template.tpl'}

-- get domain id 
SELECT  @domainID := min(id) FROM civicrm_domain;

-- CRM-8356
-- Add filter column 'filter' for 'civicrm_custom_field'
ALTER TABLE `civicrm_custom_field` ADD `filter` VARCHAR(255) NULL COMMENT 'Stores Contact Get API params contact reference custom fields. May be used for other filters in the future.';

-- CRM-8062
ALTER TABLE `civicrm_subscription_history` CHANGE `status` `status` ENUM( 'Added', 'Removed', 'Pending', 'Deleted' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'The state of the contact within the group';

-- CRM-8510
ALTER TABLE civicrm_currency
ADD UNIQUE INDEX UI_name ( name );

-- CRM-8616
DELETE FROM civicrm_currency WHERE name = 'EEK';

-- CRM-8769
INSERT INTO civicrm_state_province
  (`name`, `abbreviation`, `country_id`)
VALUES
  ('Metropolitan Manila' , 'MNL', '1170');
  
-- CRM-8902
    UPDATE civicrm_navigation SET permission ='add cases,access all cases and activities', permission_operator = 'OR'
    WHERE civicrm_navigation.name= 'New Case';

-- CRM-8780

-- add the settings table
 CREATE TABLE `civicrm_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'group name for setting element, useful in caching setting elements',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Unique name for setting',
  `value` text COLLATE utf8_unicode_ci COMMENT 'data associated with this group / name combo',
  `domain_id` int(10) unsigned NOT NULL COMMENT 'Which Domain is this menu item for',
  `contact_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Contact ID if the setting is localized to a contact',
  `is_domain` tinyint(4) DEFAULT NULL COMMENT 'Is this setting a contact specific or site wide setting?',
  `component_id` int(10) unsigned DEFAULT NULL COMMENT 'Component that this menu item belongs to',
  `created_date` datetime DEFAULT NULL COMMENT 'When was the setting created',
  `created_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_contact, who created this setting',
  PRIMARY KEY (`id`),
  KEY `index_group_name` (`group_name`,`name`),
  KEY `FK_civicrm_setting_domain_id` (`domain_id`),
  KEY `FK_civicrm_setting_contact_id` (`contact_id`),
  KEY `FK_civicrm_setting_component_id` (`component_id`),
  KEY `FK_civicrm_setting_created_id` (`created_id`),
  CONSTRAINT `FK_civicrm_setting_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_setting_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_setting_component_id` FOREIGN KEY (`component_id`) REFERENCES `civicrm_component` (`id`),
  CONSTRAINT `FK_civicrm_setting_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- CRM-8508
    SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';

    SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
    SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
    SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;

    INSERT INTO civicrm_option_value
      (option_group_id,                {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, value,                           name,               weight,                        filter, component_id)
    VALUES
        (@option_group_id_activity_type, {localize}'Change Custom Data'{/localize},{localize}''{/localize},                              (SELECT @max_val := @max_val+1), 'Change Custom Data', (SELECT @max_wt := @max_wt+1), 0, @caseCompId);

-- CRM-8739
    Update civicrm_menu set title = 'Cleanup Caches and Update Paths' where path = 'civicrm/admin/setting/updateConfigBackend';
    
-- CRM-8855
    SELECT @option_group_id_udOpt := max(id) from civicrm_option_group where name = 'user_dashboard_options';
    SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_udOpt;
    SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_udOpt;

    INSERT INTO civicrm_option_value
      (option_group_id,                {localize field='label'}label{/localize}, value, name, weight, filter, is_default, component_id)
    VALUES
        (@option_group_id_udOpt, {localize}'Assigned Activities'{/localize},  (SELECT @max_val := @max_val+1), 'Assigned Activities', (SELECT @max_wt := @max_wt+1), 0, NULL, NULL);

-- CRM-8737
   ALTER TABLE `civicrm_event` ADD `is_share` TINYINT( 4 ) NULL DEFAULT '1' COMMENT 'Can people share the event through social media?';
   ALTER TABLE `civicrm_contribution_page` ADD `is_share` TINYINT(4) NULL DEFAULT '1' COMMENT 'Can people share the contribution page through social media?';

-- CRM-8357
ALTER TABLE `civicrm_contact` CHANGE `contact_sub_type` `contact_sub_type` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'May be used to over-ride contact view and edit templates.';

UPDATE civicrm_contact SET contact_sub_type = CONCAT('', contact_sub_type, '');

-- CRM-6811
INSERT INTO `civicrm_dashboard` 
    ( `domain_id`, {localize field='label'}`label`{/localize}, `url`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `is_active`, `weight`, `fullscreen_url`, `is_fullscreen`, `is_reserved`) 
    VALUES 
    ( @domainID, {localize}'Case Dashboard Dashlet'{/localize}, 'civicrm/dashlet/casedashboard&reset=1&snippet=4', 'access CiviCase', NULL , 0, 0, 1, 4, 'civicrm/dashlet/casedashboard&reset=1&snippet=4&context=dashletFullscreen', 1, 1);

-- CRM-9059 Admin menu rebuild
SELECT @domainID := min(id) FROM civicrm_domain;
SELECT @adminlastID := id   FROM civicrm_navigation where name = 'Administer' AND domain_id = @domainID;
SELECT @customizeOld := id  FROM civicrm_navigation where name = 'Customize' AND domain_id = @domainID;
SELECT @configureOld := id  FROM civicrm_navigation where name = 'Configure' AND domain_id = @domainID;
SELECT @globalOld := id     FROM civicrm_navigation where name = 'Global Settings' AND domain_id = @domainID;
SELECT @manageOld := id     FROM civicrm_navigation where name = 'Manage' AND domain_id = @domainID;
SELECT @optionsOld := id    FROM civicrm_navigation where name = 'Option Lists' AND domain_id = @domainID;
SELECT @customizeOld := id  FROM civicrm_navigation where name = 'Customize' AND domain_id = @domainID;

DELETE from civicrm_navigation WHERE parent_id = @globalOld;
DELETE from civicrm_navigation WHERE parent_id IN (@customizeOld, @configureOld, @manageOld, @optionsOld);
DELETE from civicrm_navigation WHERE id IN (@customizeOld, @configureOld, @manageOld, @optionsOld);
UPDATE civicrm_navigation SET weight = 9 WHERE name = 'CiviCampaign' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 10 WHERE name = 'CiviCase' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 11 WHERE name = 'CiviContribute' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 12 WHERE name = 'CiviEvent' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 13 WHERE name = 'CiviGrant' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 14 WHERE name = 'CiviMail' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 15 WHERE name = 'CiviMember' AND parent_id = @adminlastID;
UPDATE civicrm_navigation SET weight = 16 WHERE name = 'CiviReport' AND parent_id = @adminlastID;

DELETE FROM civicrm_navigation WHERE name = 'Administration Console';

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin&reset=1', '{ts escape="sql" skip="true"}Administration Console{/ts}', 'Administration Console', 'administer CiviCRM', '', @adminlastID, '1', NULL, 1 );

SET @adminConsolelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/configtask&reset=1', '{ts escape="sql" skip="true"}Configuration Checklist{/ts}', 'Configuration Checklist', 'administer CiviCRM', '', @adminConsolelastID, '1', NULL, 1 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Customize Data and Screens{/ts}', 'Customize Data and Screens', 'administer CiviCRM', '', @adminlastID, '1', NULL, 3 );

SET @CustomizelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/custom/group&reset=1',      '{ts escape="sql" skip="true"}Custom Fields{/ts}', 'Custom Fields',                             'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/uf/group&reset=1',          '{ts escape="sql" skip="true"}Profiles{/ts}', 'Profiles',                                       'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/tag&reset=1',               '{ts escape="sql" skip="true"}Tags (Categories){/ts}', 'Tags (Categories)',                     'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/options/activity_type&reset=1&group=activity_type', '{ts escape="sql" skip="true"}Activity Types{/ts}', 'Activity Types',   'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 4 ), 
    ( @domainID, 'civicrm/admin/reltype&reset=1',           '{ts escape="sql" skip="true"}Relationship Types{/ts}', 'Relationship Types',                   'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/admin/options/subtype&reset=1',   '{ts escape="sql" skip="true"}Contact Types{/ts}','Contact Types',                              'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/setting/preferences/display&reset=1',   '{ts escape="sql" skip="true"}Display Preferences{/ts}', 'Display Preferences',     'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 9 ), 
    ( @domainID, 'civicrm/admin/setting/search&reset=1',    '{ts escape="sql" skip="true"}Search Preferences{/ts}',    'Search Preferences',                'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 10 ), 
    ( @domainID, 'civicrm/admin/menu&reset=1',              '{ts escape="sql" skip="true"}Navigation Menu{/ts}', 'Navigation Menu',                         'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 11 ), 
    ( @domainID, 'civicrm/admin/options/wordreplacements&reset=1','{ts escape="sql" skip="true"}Word Replacements{/ts}','Word Replacements',                'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 12 ),
    ( @domainID, 'civicrm/admin/options/custom_search&reset=1&group=custom_search', '{ts escape="sql" skip="true"}Manage Custom Searches{/ts}', 'Manage Custom Searches', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 13 ),
    ( @domainID, 'civicrm/admin/extensions&reset=1',        '{ts escape="sql" skip="true"}Manage Extensions{/ts}', 'Manage Extensions', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 14 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Dropdown Options{/ts}', 'Dropdown Options', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 8 );

SET @optionListlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES         
    ( @domainID, 'civicrm/admin/options/gender&reset=1&group=gender',                                                  '{ts escape="sql" skip="true"}Gender Options{/ts}',         'Gender Options',                           'administer CiviCRM', '',   @optionListlastID, '1', NULL,  1 ), 
    ( @domainID, 'civicrm/admin/options/individual_prefix&group=individual_prefix&reset=1',                            '{ts escape="sql" skip="true"}Individual Prefixes (Ms, Mr...){/ts}', 'Individual Prefixes (Ms, Mr...)', 'administer CiviCRM', '',   @optionListlastID, '1', NULL,  2 ), 
    ( @domainID, 'civicrm/admin/options/individual_suffix&group=individual_suffix&reset=1',                            '{ts escape="sql" skip="true"}Individual Suffixes (Jr, Sr...){/ts}', 'Individual Suffixes (Jr, Sr...)', 'administer CiviCRM', '',   @optionListlastID, '1', NULL,  3 ), 
    ( @domainID, 'civicrm/admin/options/instant_messenger_service&group=instant_messenger_service&reset=1',            '{ts escape="sql" skip="true"}Instant Messenger Services{/ts}',     'Instant Messenger Services',       'administer CiviCRM', '',   @optionListlastID, '1', NULL,  4 ), 
    ( @domainID, 'civicrm/admin/locationType&reset=1',                                                                 '{ts escape="sql" skip="true"}Location Types (Home, Work...){/ts}', 'Location Types (Home, Work...)',   'administer CiviCRM', '',   @optionListlastID, '1', NULL,  5 ), 
    ( @domainID, 'civicrm/admin/options/mobile_provider&group=mobile_provider&reset=1',                                '{ts escape="sql" skip="true"}Mobile Phone Providers{/ts}', 'Mobile Phone Providers',                   'administer CiviCRM', '',   @optionListlastID, '1', NULL,  6 ), 
    ( @domainID, 'civicrm/admin/options/phone_type&group=phone_type&reset=1',                                          '{ts escape="sql" skip="true"}Phone Types{/ts}',            'Phone Types',                              'administer CiviCRM', '',   @optionListlastID, '1', NULL,  7 ),  
    ( @domainID, 'civicrm/admin/options/website_type&group=website_type&reset=1',                                      '{ts escape="sql" skip="true"}Website Types{/ts}',          'Website Types',                            'administer CiviCRM', '',   @optionListlastID, '1', NULL,  8 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Communications{/ts}', 'Communications', 'administer CiviCRM', '', @adminlastID, '1', NULL, 4 );

SET @communicationslastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/domain&action=update&reset=1',         '{ts escape="sql" skip="true"}Organization Address and Contact Info{/ts}', 'Organization Address and Contact Info',                                      'administer CiviCRM', '', @communicationslastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/options/from_email_address&group=from_email_address&reset=1', '{ts escape="sql" skip="true"}FROM Email Addresses{/ts}', 'FROM Email Addresses',                                                 'administer CiviCRM', '', @communicationslastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/messageTemplates&reset=1',             '{ts escape="sql" skip="true"}Message Templates{/ts}',      'Message Templates',                                                                         'administer CiviCRM', '', @communicationslastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/scheduleReminders&reset=1',              '{ts escape="sql" skip="true"}Schedule Reminders{/ts}',    'Schedule Reminders',                                                                       'administer CiviCRM', '', @communicationslastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/options/preferred_communication_method&group=preferred_communication_method&reset=1',  '{ts escape="sql" skip="true"}Preferred Communication Methods{/ts}', 'Preferred Communication Methods',  'administer CiviCRM', '', @communicationslastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/labelFormats&reset=1',                                  '{ts escape="sql" skip="true"}Label Formats{/ts}',                 'Label Formats',                                                     'administer CiviCRM', '', @communicationslastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/pdfFormats&reset=1',                                    '{ts escape="sql" skip="true"}Print Page (PDF) Formats{/ts}',      'Print Page (PDF) Formats',                                          'administer CiviCRM', '', @communicationslastID, '1', NULL, 7 ), 
    ( @domainID, 'civicrm/admin/options/email_greeting&group=email_greeting&reset=1',   '{ts escape="sql" skip="true"}Email Greeting Formats{/ts}',        'Email Greeting Formats',                                            'administer CiviCRM', '', @communicationslastID, '1', NULL, 8 ), 
    ( @domainID, 'civicrm/admin/options/postal_greeting&group=postal_greeting&reset=1', '{ts escape="sql" skip="true"}Postal Greeting Formats{/ts}',       'Postal Greeting Formats',                                           'administer CiviCRM', '', @communicationslastID, '1', NULL, 9 ), 
    ( @domainID, 'civicrm/admin/options/addressee&group=addressee&reset=1',             '{ts escape="sql" skip="true"}Addressee Formats{/ts}',             'Addressee Formats',                                                 'administer CiviCRM', '', @communicationslastID, '1', NULL, 10 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Localization{/ts}', 'Localization', 'administer CiviCRM', '', @adminlastID, '1', NULL, 6 );

SET @locallastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/setting/localization&reset=1',          '{ts escape="sql" skip="true"}Languages, Currency, Location{/ts}', 'Languages, Currency, Locations',   'administer CiviCRM', '', @locallastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/setting/preferences/address&reset=1',   '{ts escape="sql" skip="true"}Address Settings{/ts}',               'Address Settings',                 'administer CiviCRM', '', @locallastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/setting/date&reset=1',                  '{ts escape="sql" skip="true"}Date Format{/ts}',                    'Date Formats',                     'administer CiviCRM', '', @locallastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/options/languages&group=languages&reset=1', '{ts escape="sql" skip="true"}Preferred Language Options{/ts}', 'Preferred Language Options',       'administer CiviCRM', '', @locallastID, '1', NULL, 4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES     
    ( @domainID, NULL,                                                  '{ts escape="sql" skip="true"}Users and Permissions{/ts}',          'Users and Permissions',            'administer CiviCRM', '', @adminlastID, '1', NULL, 7 );

SET @usersPermslastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/access&reset=1',       '{ts escape="sql" skip="true"}Permissions (Access Control){/ts}',    'Permissions (Access Control)',     'administer CiviCRM', '', @usersPermslastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/synchUser&reset=1',    '{ts escape="sql" skip="true"}Synchronize Users to Contacts{/ts}',   'Synchronize Users to Contacts',    'administer CiviCRM', '', @usersPermslastID, '1', NULL, 2 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,                                 '{ts escape="sql" skip="true"}System Settings{/ts}',                 'System Settings',                      'administer CiviCRM', '', @adminlastID, '1', NULL, 8 );

SET @systemSettingslastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/setting/component&reset=1',             '{ts escape="sql" skip="true"}Enable CiviCRM Components{/ts}', 'Enable Components',                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/admin/setting/smtp&reset=1',                  '{ts escape="sql" skip="true"}Outbound Email (SMTP/Sendmail){/ts}', 'Outbound Email',                   'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/admin/paymentProcessor&reset=1',              '{ts escape="sql" skip="true"}Payment Processors{/ts}', 'Payment Processors',                           'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 3 ), 
    ( @domainID, 'civicrm/admin/setting/mapping&reset=1',               '{ts escape="sql" skip="true"}Mapping and Geocoding{/ts}', 'Mapping and Geocoding',                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 4 ), 
    ( @domainID, 'civicrm/admin/setting/misc&reset=1',                  '{ts escape="sql" skip="true"}Undelete, Logging and ReCAPTCHA{/ts}', 'Undelete, Logging and ReCAPTCHA', 'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/admin/setting/path&reset=1',                  '{ts escape="sql" skip="true"}Directories{/ts}',        'Directories',                                  'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/setting/url&reset=1',                   '{ts escape="sql" skip="true"}Resource URLs{/ts}',      'Resource URLs',                                'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 7 ), 
    ( @domainID, 'civicrm/admin/setting/updateConfigBackend&reset=1',   '{ts escape="sql" skip="true"}Cleanup Caches and Update Paths{/ts}', 'Cleanup Caches and Update Paths', 'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/admin/setting/uf&reset=1',                    '{ts escape="sql" skip="true"}CMS Database Integration{/ts}',    'CMS Integration',                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 9 ), 
    ( @domainID, 'civicrm/admin/options/safe_file_extension&group=safe_file_extension&reset=1', '{ts escape="sql" skip="true"}Safe File Extensions{/ts}', 'Safe File Extensions','administer CiviCRM', '',@systemSettingslastID, '1', NULL, 10 ), 
    ( @domainID, 'civicrm/admin/options?reset=1',                       '{ts escape="sql" skip="true"}Option Groups{/ts}', 'Option Groups',                                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 11 ), 
    ( @domainID, 'civicrm/admin/mapping&reset=1',                       '{ts escape="sql" skip="true"}Import/Export Mappings{/ts}', 'Import/Export Mappings',                   'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 12 ), 
    ( @domainID, 'civicrm/admin/setting/debug&reset=1',                 '{ts escape="sql" skip="true"}Debugging and Error Handling{/ts}','Debugging and Error Handling',        'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 13 ),
    ( @domainID, 'civicrm/admin/setting/preferences/multisite&reset=1', '{ts escape="sql" skip="true"}Multi Site Settings{/ts}', 'Multi Site Settings',                         'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 14 ); 

SELECT @campaignAdminID := id   FROM civicrm_navigation where name = 'CiviCampaign' AND parent_id = @adminlastID;
SELECT @eventAdminID := id      FROM civicrm_navigation where name = 'CiviEvent'    AND parent_id = @adminlastID;
SELECT @mailAdminID := id       FROM civicrm_navigation where name = 'CiviMail'      AND parent_id = @adminlastID;
SELECT @memberAdminID := id     FROM civicrm_navigation where name = 'CiviMember' AND parent_id = @adminlastID;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/setting/preferences/campaign&reset=1',  '{ts escape="sql" skip="true"}CiviCampaign Component Settings{/ts}', 'CiviCampaign Component Settings','administer CiviCampaign', '', @campaignAdminID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/setting/preferences/event&reset=1',     '{ts escape="sql" skip="true"}CiviEvent Component Settings{/ts}', 'CiviEvent Component Settings','access CiviEvent,administer CiviCRM', 'AND', @eventAdminID, '1', NULL, 13 ), 
    ( @domainID, 'civicrm/admin/setting/preferences/mailing&reset=1',   '{ts escape="sql" skip="true"}CiviMail Component Settings{/ts}', 'CiviMail Component Settings','access CiviMail,administer CiviCRM', 'AND', @mailAdminID, '1', NULL, 6 ), 
    ( @domainID, 'civicrm/admin/setting/preferences/member&reset=1',    '{ts escape="sql" skip="true"}CiviMember Component Settings{/ts}', 'CiviMember Component Settings','access CiviMember,administer CiviCRM', 'AND', @memberAdminID, '1', NULL, 5 ), 
    ( @domainID, 'civicrm/admin/options/event_badge&group=event_badge&reset=1', '{ts escape="sql" skip="true"}Event Badge Formats{/ts}', 'Event Badge Formats', 'access CiviEvent,administer CiviCRM', 'AND', @eventAdminID, '1', NULL, 11 ); 

-- CRM-9113
ALTER TABLE `civicrm_report_instance` ADD `grouprole` VARCHAR( 1024 ) NULL AFTER `permission`;

-- CRM-8762 Fix option_group table
ALTER TABLE civicrm_option_group CHANGE `is_reserved` `is_reserved` TINYINT DEFAULT 1;
UPDATE civicrm_option_group SET `is_reserved` = 1;
{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE civicrm_option_group SET label_{$locale} = description_{$locale} WHERE label_{$locale} IS NULL;
  {/foreach}
{else}
  UPDATE civicrm_option_group SET `label` = `description` WHERE `label` IS NULL;
{/if}

-- CRM-9112
  ALTER TABLE `civicrm_dedupe_rule_group` ADD `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Label of the rule group';

ALTER TABLE `civicrm_dedupe_rule_group` ADD `is_reserved` TINYINT( 4 ) NULL DEFAULT NULL COMMENT 'Is this a reserved rule - a rule group that has been optimized and cannot be changed by the admin';

UPDATE `civicrm_dedupe_rule_group` SET `name` = CONCAT( REPLACE( `name`, '-', '' ), '-', id );

-- the fuzzy default dedupe rules
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, level, is_default, name, title, is_reserved)
VALUES ('Individual', 20, 'Fuzzy', 1, 'IndividualFuzzy', 'Individual Fuzzy In-built', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'first_name', 5),
       (@drgid, 'civicrm_contact', 'last_name',  7),
       (@drgid, 'civicrm_email'  , 'email',     10);

-- the strict dedupe rules
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, level, is_default, name, title, is_reserved)
VALUES ('Individual', 10, 'Strict', 1, 'IndividualStrict', 'Individual Strict In-built', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_email', 'email', 10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, level, is_default, name, title, is_reserved)
VALUES ('Individual', 15, 'Strict', 0, 'IndividualComplete', 'Individual Complete Inbuilt', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'first_name',     '5'),
       (@drgid, 'civicrm_contact', 'last_name',      '5'),
       (@drgid, 'civicrm_address', 'street_address', '5'),
       (@drgid, 'civicrm_contact', 'middle_name',    '1'),
       (@drgid, 'civicrm_contact', 'suffix_id',      '1');

-- CRM-9120
{if $multilingual}
  {foreach from=$locales item=locale}
    ALTER TABLE `civicrm_location_type` ADD display_name_{$locale} VARCHAR( 64 ) DEFAULT NULL  COMMENT 'Location Type Display Name.' AFTER `name`;
    UPDATE `civicrm_location_type` SET display_name_{$locale} = `name`;
  {/foreach}
{else}
  ALTER TABLE `civicrm_location_type` ADD `display_name` VARCHAR( 64 ) DEFAULT NULL  COMMENT 'Location Type Display Name.' AFTER `name`;
  UPDATE `civicrm_location_type` SET `display_name` = `name`;
{/if}


-- CRM-9125
ALTER TABLE `civicrm_contribution_recur` ADD `contribution_type_id` int(10) unsigned NULL COMMENT 'FK to Contribution Type';
ALTER TABLE `civicrm_contribution_recur` ADD CONSTRAINT `FK_civicrm_contribution_recur_contribution_type_id` FOREIGN KEY (`contribution_type_id`) REFERENCES `civicrm_contribution_type` (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_contribution_recur` ADD `payment_instrument_id` int(10) unsigned NULL COMMENT 'FK to Payment Instrument';
ALTER TABLE `civicrm_contribution_recur` ADD INDEX UI_contribution_recur_payment_instrument_id ( payment_instrument_id );

ALTER TABLE `civicrm_contribution_recur` ADD `campaign_id` int(10) unsigned NULL COMMENT 'The campaign for which this contribution has been triggered.';
ALTER TABLE `civicrm_contribution_recur` ADD CONSTRAINT `FK_civicrm_contribution_recur_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `civicrm_campaign` (`id`) ON DELETE SET NULL;

UPDATE `civicrm_contribution_recur` ccr INNER JOIN `civicrm_contribution` cc ON ccr.id = cc.contribution_recur_id SET ccr.campaign_id = cc.campaign_id, ccr.payment_instrument_id = cc.payment_instrument_id, ccr.contribution_type_id = cc.contribution_type_id;

-- CRM-8962
INSERT INTO civicrm_action_mapping ( entity, entity_value, entity_value_label, entity_status, entity_status_label, entity_date_start, entity_date_end, entity_recipient ) 
VALUES
('civicrm_participant', 'event_type', 'Event Type', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
('civicrm_participant', 'civicrm_event', 'Event Name', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts');

INSERT INTO civicrm_option_group
      (name, {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, is_reserved, is_active)
VALUES
      ('event_contacts', {localize}'{ts escape="sql"}Event Recipients{/ts}'{/localize}, {localize}'{ts escape="sql"}Event Recipients{/ts}'{/localize}, 1, 1),
      ('contact_reference_options', {localize}'{ts escape="sql"}Contact Reference Autocomplete Options{/ts}'{/localize}, {localize}'{ts escape="sql"}Contact Reference Autocomplete Options{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_ere := max(id) from civicrm_option_group where name = 'event_contacts';

INSERT INTO civicrm_option_value 
   (option_group_id, {localize field='label'}label{/localize}, value, name,  filter,  weight, is_active ) 
VALUES
   (@option_group_id_ere, {localize}'{ts escape="sql"}Participant Role{/ts}'{/localize}, 1, 'participant_role', 0,  1, 1 );

ALTER TABLE civicrm_action_schedule ADD `absolute_date` date DEFAULT NULL COMMENT 'Date on which the reminder be sent.';
ALTER TABLE civicrm_action_schedule ADD `recipient_listing` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'listing based on recipient field.';
  
  
-- CRM-8534
ALTER TABLE civicrm_pcp_block 
      DROP FOREIGN KEY FK_civicrm_pcp_block_entity_id,
      DROP INDEX FK_civicrm_pcp_block_entity_id;

ALTER TABLE civicrm_pcp 
      DROP FOREIGN KEY FK_civicrm_pcp_contribution_page_id,
      DROP INDEX FK_civicrm_pcp_contribution_page_id;

ALTER TABLE `civicrm_pcp` 
  ADD `page_type` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'contribute' AFTER `contribution_page_id`;
ALTER TABLE `civicrm_pcp` 
  CHANGE `contribution_page_id` `page_id` INT( 10 ) UNSIGNED NOT NULL COMMENT 'The Page which triggered this pcp';
ALTER TABLE `civicrm_pcp`
  ADD `pcp_block_id` int(10) unsigned NOT NULL COMMENT 'The pcp block that this pcp page was created from' AFTER `page_type`;

UPDATE `civicrm_pcp`
  SET `page_type` = 'contribute' WHERE `page_type` = '' OR `page_type` IS NULL;
UPDATE `civicrm_pcp` `pcp`
  SET `pcp_block_id` = (SELECT `id` FROM `civicrm_pcp_block` `pb` WHERE `pb`.`entity_id` = `pcp`.`page_id`);

ALTER TABLE `civicrm_pcp_block`
  ADD `target_entity_type` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'contribute' AFTER `entity_id`;
ALTER TABLE `civicrm_pcp_block`
  ADD `target_entity_id` int(10) unsigned NOT NULL COMMENT 'The entity that this pcp targets' AFTER `target_entity_type`;
UPDATE `civicrm_pcp_block`
  SET `target_entity_id` = `entity_id` WHERE `target_entity_id` = '' OR `target_entity_id` IS NULL;

ALTER TABLE `civicrm_pcp` DROP COLUMN `referer`;

UPDATE civicrm_navigation SET url = 'civicrm/admin/pcp?reset=1&page_type=contribute' WHERE url = 'civicrm/admin/pcp&reset=1';

SELECT @lastEventId        := MAX(id) FROM civicrm_navigation where name = 'Events' AND domain_id = @domainID;
SELECT @adminEventId       := MAX(id) FROM civicrm_navigation where name = 'CiviEvent' AND domain_id = @domainID;
SELECT @lastEventIdWeight  := MAX(weight)+1 FROM civicrm_navigation where parent_id = @lastEventId;
SELECT @adminEventIdWeight := MAX(weight)+1 FROM civicrm_navigation where parent_id = @adminEventId;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/admin/pcp?reset=1&page_type=event',            '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',   'Personal Campaign Pages',   'access CiviEvent,administer CiviCRM', 'AND', @lastEventId, '1', 1, @lastEventIdWeight ),
    ( @domainID, 'civicrm/admin/pcp?reset=1&page_type=event',            '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',   'Personal Campaign Pages',   'access CiviEvent,administer CiviCRM', 'AND', @adminEventId, '1', 1, @adminEventIdWeight );

-- CRM-8358 - consolidated cron jobs

CREATE TABLE `civicrm_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Job Id',
  `domain_id` int(10) unsigned NOT NULL COMMENT 'Which Domain is this scheduled job for',
  `run_frequency` enum('Hourly','Daily','Always') COLLATE utf8_unicode_ci DEFAULT 'Daily' COMMENT 'Scheduled job run frequency.',
  `last_run` datetime DEFAULT NULL COMMENT 'When was this cron entry last run',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Title of the job',
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Description of the job',
  `api_prefix` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Prefix of the job api call',
  `api_entity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Entity of the job api call',
  `api_action` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Action of the job api call',
  `parameters` text COLLATE utf8_unicode_ci COMMENT 'List of parameters to the command.',
  `is_active` tinyint(4) DEFAULT NULL COMMENT 'Is this job active?',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_job_domain_id` (`domain_id`),
  CONSTRAINT `FK_civicrm_job_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `civicrm_job_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Job log entry Id',
  `domain_id` int(10) unsigned NOT NULL COMMENT 'Which Domain is this scheduled job for',
  `run_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Log entry date',
  `job_id` int(10) unsigned DEFAULT NULL COMMENT 'Pointer to job id - not a FK though, just for logging purposes',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Title of the job',
  `command` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Full path to file containing job script',
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Title line of log entry',
  `data` text COLLATE utf8_unicode_ci COMMENT 'Potential extended data for specific job run (e.g. tracebacks).',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_job_log_domain_id` (`domain_id`),
  CONSTRAINT `FK_civicrm_job_log_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_prefix, api_entity, api_action, parameters, is_active ) 
VALUES 
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Mailings scheduler{/ts}',          '{ts escape="sql" skip="true"}Sends out scheduled mailings{/ts}',                                         'civicrm_api3', 'job', 'process_mailing',         'user=USERNAME\r\npassword=PASSWORD\r\nkey=SITE_KEY', 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Bounces fetcher{/ts}',             '{ts escape="sql" skip="true"}Fetches bounces from mailings and writes them to mailing statistics{/ts}',  'civicrm_api3', 'job', 'fetch_bounces',           'user=USERNAME\r\npassword=PASSWORD\r\nkey=SITE_KEY', 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Activity processor{/ts}',          '{ts escape="sql" skip="true"}{/ts}',                                                                     'civicrm_api3', 'job', 'fetch_activities',        'user=USERNAME\r\npassword=PASSWORD\r\nkey=SITE_KEY', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Pledge record processor{/ts}',     '{ts escape="sql" skip="true"}Updates pledge records and sends out reminders{/ts}',                       'civicrm_api3', 'job', 'process_pledge',          'version=3\r\n', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Address geocoder{/ts}',            '{ts escape="sql" skip="true"}Goes through addresses and geocodes them (requires Geocoding API on){/ts}', 'civicrm_api3', 'job', 'geocode',                 'version=3\r\n', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Greeting updater{/ts}',            '{ts escape="sql" skip="true"}Goes through contact records and updates greeting settings{/ts}',           'civicrm_api3', 'job', 'update_greeting',         'version=3\r\n', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Report sender{/ts}',               '{ts escape="sql" skip="true"}Generates and sends out reports via email{/ts}',                            'civicrm_api3', 'job', 'mail_reports',            'version=3\r\n', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Scheduled reminders sender{/ts}',  '{ts escape="sql" skip="true"}Sends out scheduled reminders via email.{/ts}',                             'civicrm_api3', 'job', 'send_reminder',           'version=3\r\n', 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Participant status processor{/ts}','{ts escape="sql" skip="true"}Adjusts event participant statuses based on time{/ts}',                     'civicrm_api3', 'job', 'process_participant',     'version=3\r\n', 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Membership status processor{/ts}', '{ts escape="sql" skip="true"}Adjusts event membership statuses based on time{/ts}',                      'civicrm_api3', 'job', 'process_membership',      'version=3\r\n', 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Membership reminder date processor{/ts}','{ts escape="sql" skip="true"}Adjusts membership reminder dates based on time{/ts}',                     'civicrm_api3', 'job', 'process_process_membership_reminder_date',     'version=3\r\n', 0);

--CRM 9135
ALTER TABLE civicrm_contribution_recur
 ADD is_email_receipt TINYINT (4) COMMENT 'if true, receipt is automatically emailed to contact on each successful payment' AFTER payment_processor_id;

-- /*****   Civicrm Multi-Event Registration   ***********/ 

CREATE TABLE civicrm_event_carts (
     id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Cart Id',
     user_id int unsigned    COMMENT 'FK to civicrm_contact who created this cart',
     coupon_code varchar(255) DEFAULT NULL,
     completed tinyint   DEFAULT 0,
     PRIMARY KEY ( id ),
     CONSTRAINT FK_civicrm_event_carts_user_id FOREIGN KEY (user_id)
REFERENCES civicrm_contact(id) ON DELETE SET NULL
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

CREATE TABLE civicrm_events_in_carts (
     id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Event In Cart Id',
     event_id int unsigned    COMMENT 'FK to Event ID',
     event_cart_id int unsigned    COMMENT 'FK to Event Cart ID',
    PRIMARY KEY ( id ),
     CONSTRAINT FK_civicrm_events_in_carts_event_id FOREIGN KEY (event_id)
REFERENCES civicrm_event(id) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_events_in_carts_event_cart_id FOREIGN KEY
(event_cart_id) REFERENCES civicrm_event_carts(id) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;


ALTER TABLE civicrm_participant
    ADD discount_amount int unsigned DEFAULT 0 COMMENT 'Discount Amount';
ALTER TABLE civicrm_participant
    ADD cart_id int unsigned DEFAULT NULL COMMENT 'FK to civicrm_event_carts';
ALTER TABLE civicrm_participant
    ADD CONSTRAINT FK_civicrm_participant_cart_id FOREIGN KEY (cart_id)
        REFERENCES civicrm_event_carts(id) ON DELETE SET NULL;

-- XXX a hint to the payment form.  Can someone make this go away?
ALTER TABLE civicrm_participant
    ADD must_wait TINYINT   DEFAULT 0 COMMENT 'On Waiting List';


SELECT @pending_id                 := MAX(id) + 1 FROM civicrm_participant_status_type;
INSERT INTO civicrm_participant_status_type
  (id,          name,                         {localize field='label'}label{/localize},                                                       class,      is_reserved, is_active, is_counted, weight,      visibility_id)
VALUES
  (@pending_id, 'Pending in cart',            {localize}'{ts escape="sql"}Pending in cart{/ts}'{/localize},                     'Pending',  1,           1,         0,          @pending_id, 2            );


ALTER TABLE civicrm_event
    ADD parent_event_id int unsigned DEFAULT NULL COMMENT 'Implicit FK to civicrm_event: parent event';
ALTER TABLE civicrm_event
    ADD slot_label_id int unsigned DEFAULT NULL COMMENT 'Subevent slot label. Implicit FK to civicrm_option_value where option_group = conference_slot.';

INSERT INTO 
   `civicrm_option_group` (`name`, {localize field='label'}`label`{/localize}, {localize field='description'}`description`{/localize}, `is_reserved`, `is_active`)
VALUES 
   ('conference_slot'               , {localize}'{ts escape="sql"}Conference Slot{/ts}'{/localize}, {localize}'{ts escape="sql"}Conference Slot{/ts}'{/localize}                    , 1, 1);

SELECT @option_group_id_conference_slot := max(id) from civicrm_option_group where name = 'conference_slot';

INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
VALUES
   (@option_group_id_conference_slot, {localize}'{ts escape="sql"}Morning Sessions{/ts}'{/localize}, 1, '{ts escape="sql"}Morning Sessions{/ts}', NULL, 0, NULL, 1, {localize}'{ts escape="sql"}NULL{/ts}'{/localize}, 0, 0, 1, NULL, NULL),
   (@option_group_id_conference_slot, {localize}'{ts escape="sql"}Evening Sessions{/ts}'{/localize}, 2, '{ts escape="sql"}Evening Sessions{/ts}', NULL, 0, NULL, 2, {localize}'{ts escape="sql"}NULL{/ts}'{/localize}, 0, 0, 1, NULL, NULL);

SELECT @msg_tpl_workflow_event := MAX(id)     FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_event';
SELECT @weight                 := MAX(weight) + 1 FROM civicrm_option_value WHERE option_group_id = @msg_tpl_workflow_event;

INSERT INTO civicrm_option_value
  (option_group_id,         name,                         {localize field='label'}label{/localize},                                         value,   weight)
VALUES
  (@msg_tpl_workflow_event, 'event_registration_receipt', {localize}'{ts escape="sql"}Events - Receipt only{/ts}'{/localize}, @weight, @weight);

{* SELECT @tpl_ovid_$vName := MAX(id) FROM civicrm_option_value WHERE option_group_id = @tpl_ogid_$gName AND name = '$vName'; *}
{* INSERT INTO civicrm_msg_template *}

-- CRM-8532
UPDATE `civicrm_dashboard` SET url = REPLACE( url, 'snippet=4', 'snippet=5' ), fullscreen_url = REPLACE( fullscreen_url, 'snippet=4', 'snippet=5' );

{if $multilingual}
  {foreach from=$locales item=locale}
    ALTER TABLE civicrm_option_group CHANGE label_{$locale} title_{$locale} varchar(255);
  {/foreach}
{else}
  ALTER TABLE civicrm_option_group CHANGE label title varchar(255);
{/if}
