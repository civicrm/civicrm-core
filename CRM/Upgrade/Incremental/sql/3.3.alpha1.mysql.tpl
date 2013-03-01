-- CRM-6696
ALTER TABLE civicrm_option_value {localize field='description'}MODIFY COLUMN description text{/localize};

-- CRM-6442
SELECT @option_group_id_website := MAX(id) from civicrm_option_group where name = 'website_type';
SELECT @max_value               := MAX(ROUND(value)) from civicrm_option_value where option_group_id = @option_group_id_website;
SELECT @max_weight              := MAX(ROUND(weight)) from civicrm_option_value where option_group_id = @option_group_id_website;;

INSERT INTO civicrm_option_value
        (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
	(@option_group_id_website, {localize}'Main'{/localize}, @max_value+1, 'Main', NULL, 0, NULL, @max_weight+1, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL);
	
-- CRM-6763
UPDATE civicrm_option_group 
   SET is_reserved = 0
 WHERE civicrm_option_group.name = 'encounter_medium';

-- CRM-6814
ALTER TABLE `civicrm_note` 
  ADD `privacy` INT( 10 ) NOT NULL COMMENT 'Foreign Key to Note Privacy Level (which is an option value pair and hence an implicit FK)';

UPDATE `civicrm_note` SET `privacy` = '0' WHERE 1;

INSERT INTO civicrm_option_group
      (name, {localize field='description'}description{/localize}, is_reserved, is_active)
VALUES
      ('note_privacy', {localize}'Privacy levels for notes'{/localize}, 0, 1);

SELECT @option_group_id_notePrivacy := max(id) from civicrm_option_group where name = 'note_privacy';

INSERT INTO civicrm_option_value
      (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
      (@option_group_id_notePrivacy, {localize}'None'{/localize}        , 0, '', NULL, 0, 1, 1, 0, 1, 1, NULL, NULL),
      (@option_group_id_notePrivacy, {localize}'Author Only'{/localize} , 1, '', NULL, 0, 0, 2, 0, 1, 1, NULL, NULL);

-- CRM-6748
UPDATE civicrm_navigation SET url = 'civicrm/admin/contribute/add&reset=1&action=add'
        WHERE civicrm_navigation.name = 'New Contribution Page';

-- CRM-6507
ALTER TABLE civicrm_participant 
   CHANGE role_id role_id varchar(128) collate utf8_unicode_ci NULL default NULL COMMENT 'Participant role ID. Implicit FK to civicrm_option_value where option_group = participant_role.';

--
-- Campaign upgrade.
--
-- CRM-6232
CREATE TABLE `civicrm_campaign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Campaign ID.',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Name of the Campaign.',
  `title` varchar(255) NULL DEFAULT NULL COMMENT 'Title of the Campaign.',
  `description` text collate utf8_unicode_ci default NULL COMMENT 'Full description of Campaign.',
  `start_date` datetime default NULL COMMENT 'Date and time that Campaign starts.',
  `end_date` datetime default NULL COMMENT 'Date and time that Campaign ends.',
  `campaign_type_id` int unsigned DEFAULT NULL COMMENT 'Campaign Type ID.Implicit FK to civicrm_option_value where option_group = campaign_type',
  `status_id` int unsigned DEFAULT NULL COMMENT 'Campaign status ID.Implicit FK to civicrm_option_value where option_group = campaign_status',
  `external_identifier` int unsigned NULL DEFAULT NULL COMMENT 'Unique trusted external ID (generally from a legacy app/datasource). Particularly useful for deduping operations.',
  `parent_id` int unsigned NULL DEFAULT NULL COMMENT 'Optional parent id for this Campaign.',
  `is_active` boolean NOT NULL DEFAULT 1 COMMENT 'Is this Campaign enabled or disabled/cancelled?',
  `created_id` int unsigned NULL DEFAULT NULL COMMENT 'FK to civicrm_contact, who created this Campaign.',
  `created_date` datetime default NULL COMMENT 'Date and time that Campaign was created.',
  `last_modified_id` int unsigned NULL DEFAULT NULL COMMENT 'FK to civicrm_contact, who recently edited this Campaign.',
  `last_modified_date` datetime default NULL COMMENT 'Date and time that Campaign was edited last time.',
  PRIMARY KEY ( id ),
  INDEX UI_campaign_type_id (campaign_type_id),
  INDEX UI_campaign_status_id (status_id),
  UNIQUE INDEX UI_external_identifier (external_identifier),
  CONSTRAINT FK_civicrm_campaign_created_id FOREIGN KEY (created_id) REFERENCES civicrm_contact(id) ON DELETE SET NULL,
  CONSTRAINT FK_civicrm_campaign_last_modified_id FOREIGN KEY (last_modified_id) REFERENCES civicrm_contact(id) ON DELETE SET NULL,
  CONSTRAINT FK_civicrm_campaign_parent_id FOREIGN KEY (parent_id) REFERENCES civicrm_campaign(id) ON DELETE SET NULL
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `civicrm_campaign_group` ( 
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Campaign Group id.',
  `campaign_id` int unsigned NOT NULL COMMENT 'Foreign key to the activity Campaign.',
  `group_type` enum('Include','Exclude') NULL DEFAULT NULL COMMENT 'Type of Group.',
  `entity_table` varchar(64) NULL DEFAULT NULL COMMENT 'Name of table where item being referenced is stored.',
  `entity_id` int unsigned DEFAULT NULL COMMENT 'Entity id of referenced table.',
  PRIMARY KEY ( id ),
  CONSTRAINT FK_civicrm_campaign_group_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE CASCADE
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `civicrm_survey` ( 
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Campaign Group id.',
  `title` varchar(255) NOT NULL COMMENT 'Title of the Survey.',
  `campaign_id` int unsigned DEFAULT NULL COMMENT 'Foreign key to the activity Campaign.',
  `activity_type_id` int unsigned DEFAULT NULL COMMENT 'Implicit FK to civicrm_option_value where option_group = activity_type',
  `recontact_interval` text collate utf8_unicode_ci DEFAULT NULL COMMENT 'Recontact intervals for each status.',
  `instructions` text collate utf8_unicode_ci DEFAULT NULL COMMENT 'Script instructions for volunteers to use for the survey.',
  `release_frequency` int unsigned DEFAULT NULL COMMENT 'Number of days for recurrence of release.',
  `max_number_of_contacts` int unsigned DEFAULT NULL COMMENT 'Maximum number of contacts to allow for survey.',
  `default_number_of_contacts` int unsigned DEFAULT NULL COMMENT 'Default number of contacts to allow for survey.',
  `is_active` boolean NOT NULL DEFAULT 1 COMMENT 'Is this survey enabled or disabled/cancelled?',
  `is_default` boolean NOT NULL DEFAULT 0 COMMENT 'Is this default survey?',
  `created_id` int unsigned NULL DEFAULT NULL COMMENT 'FK to civicrm_contact, who created this Survey.',
  `created_date` datetime default NULL COMMENT 'Date and time that Survey was created.',
  `last_modified_id` int unsigned NULL DEFAULT NULL COMMENT 'FK to civicrm_contact, who recently edited this Survey.',
  `last_modified_date` datetime default NULL COMMENT 'Date and time that Survey was edited last time.',
  `result_id` int unsigned NULL DEFAULT NULL COMMENT 'Used to store option group id.',
 PRIMARY KEY ( id ),
  CONSTRAINT FK_civicrm_survey_campaign_id FOREIGN KEY (campaign_id) REFERENCES civicrm_campaign(id) ON DELETE CASCADE,
  INDEX UI_activity_type_id (activity_type_id),
  CONSTRAINT FK_civicrm_survey_created_id FOREIGN KEY (created_id) REFERENCES civicrm_contact(id) ON DELETE SET NULL,
  CONSTRAINT FK_civicrm_survey_last_modified_id FOREIGN KEY (last_modified_id) REFERENCES civicrm_contact(id) ON DELETE SET NULL
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--add result column to activity table.
ALTER TABLE `civicrm_activity` ADD `result` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'Currently being used to store result for survey activity. FK to option value.' AFTER `original_id`;

--insert campaign component.
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviCampaign'  , 'CRM_Campaign' );

INSERT INTO civicrm_option_group
       	(`name`, {localize field='description'}description{/localize}, `is_active`)
VALUES
	('campaign_type'    , {localize}'Campaign Type'{/localize}     , 1 ),
   	('campaign_status'  , {localize}'Campaign Status'{/localize}   , 1 );

--insert values for Compaign Types, Campaign Status and Activity types.
   
SELECT @option_group_id_campaignType   := max(id) from civicrm_option_group where name = 'campaign_type';
SELECT @option_group_id_campaignStatus := max(id) from civicrm_option_group where name = 'campaign_status';
SELECT @option_group_id_act            := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @campaignCompId                 := max(id) FROM civicrm_component where name    = 'CiviCampaign';
SELECT @max_campaign_act_val           := MAX(ROUND(value)) from civicrm_option_value where option_group_id = @option_group_id_act;
SELECT @max_campaign_act_wt            := MAX(ROUND(weight)) from civicrm_option_value where option_group_id = @option_group_id_act;

INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `weight`, `is_active`, `component_id` ) 
VALUES
  (@option_group_id_campaignType, {localize}'Direct Mail'{/localize},      1, 'Direct Mail',       1,   1, NULL ),
  (@option_group_id_campaignType, {localize}'Referral Program'{/localize}, 2, 'Referral Program',  2,   1, NULL ),
  (@option_group_id_campaignType, {localize}'Voter Engagement'{/localize}, 3, 'Voter Engagement',  3,   1, NULL ),

  (@option_group_id_campaignStatus, {localize}'Planned'{/localize},        1, 'Planned',           1,   1, NULL ), 
  (@option_group_id_campaignStatus, {localize}'In Progress'{/localize},    2, 'In Progress',       2,   1, NULL ),
  (@option_group_id_campaignStatus, {localize}'Completed'{/localize},      3, 'Completed',         3,   1, NULL ),
  (@option_group_id_campaignStatus, {localize}'Cancelled'{/localize},      4, 'Cancelled',         4,   1, NULL ),

  (@option_group_id_act, {localize}'Survey'{/localize},                   (SELECT @max_campaign_act_val := @max_campaign_act_val + 1 ), 'Survey',           (SELECT @max_campaign_act_wt := @max_campaign_act_wt + 1 ),   1, @campaignCompId ),
  (@option_group_id_act, {localize}'Canvass'{/localize},                  (SELECT @max_campaign_act_val := @max_campaign_act_val + 1 ), 'Canvass',          (SELECT @max_campaign_act_wt := @max_campaign_act_wt + 1 ),   1, @campaignCompId ),
  (@option_group_id_act, {localize}'PhoneBank'{/localize},                (SELECT @max_campaign_act_val := @max_campaign_act_val + 1 ), 'PhoneBank',        (SELECT @max_campaign_act_wt := @max_campaign_act_wt + 1 ),   1, @campaignCompId ),
  (@option_group_id_act, {localize}'WalkList'{/localize},                 (SELECT @max_campaign_act_val := @max_campaign_act_val + 1 ), 'WalkList',         (SELECT @max_campaign_act_wt := @max_campaign_act_wt + 1 ),   1, @campaignCompId ),
  (@option_group_id_act, {localize}'Petition'{/localize},                 (SELECT @max_campaign_act_val := @max_campaign_act_val + 1 ), 'Petition',         (SELECT @max_campaign_act_wt := @max_campaign_act_wt + 1 ),   1, @campaignCompId );

--campaign navigation.
SELECT @domainID        := MIN(id) FROM civicrm_domain;
SELECT @nav_other_id    := id FROM civicrm_navigation WHERE name = 'Other';
SELECT @nav_other_wt    := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_other_id;

--insert campaigns permissions in 'Other' navigation menu permissions.
UPDATE  civicrm_navigation 
   SET  permission = CONCAT( permission, ',administer CiviCampaign,manage campaign,reserve campaign contacts,release campaign contacts,interview campaign contacts' ) 
 WHERE  id = @nav_other_id;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql"}Campaigns{/ts}', 'Campaigns', 'interview campaign contacts,release campaign contacts,reserve campaign contacts,manage campaign,administer CiviCampaign', 'OR', @nav_other_id, '1', NULL, (SELECT @nav_other_wt := @nav_other_wt + 1) );

SELECT @nav_campaign_id    := id FROM civicrm_navigation WHERE name = 'Campaigns';

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/campaign&reset=1',        '{ts escape="sql"}Dashboard{/ts}', 'Dashboard', 'administer CiviCampaign', '', @nav_campaign_id, '1', NULL, 1 );

SET @campaigndashboardlastID:=LAST_INSERT_ID();

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES    
    ( @domainID, 'civicrm/campaign&reset=1&subPage=survey',        '{ts escape="sql"}Surveys{/ts}', 'Survey Dashboard', 'administer CiviCampaign', '', @campaigndashboardlastID, '1', NULL, 1 ), 
    ( @domainID, 'civicrm/campaign&reset=1&subPage=petition',        '{ts escape="sql"}Petition{/ts}', 'Petition Dashboard', 'administer CiviCampaign', '', @campaigndashboardlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/campaign&reset=1&subPage=campaign',        '{ts escape="sql"}Campaigns{/ts}', 'Campaign Dashboard', 'administer CiviCampaign', '', @campaigndashboardlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/campaign/add&reset=1',        '{ts escape="sql"}New Campaign{/ts}', 'New Campaign', 'administer CiviCampaign', '', @nav_campaign_id, '1', NULL, 2 ), 
    ( @domainID, 'civicrm/survey/add&reset=1',        '{ts escape="sql"}New Survey{/ts}', 'New Survey', 'administer CiviCampaign', '', @nav_campaign_id, '1', NULL, 3 ),
    ( @domainID, 'civicrm/petition/add&reset=1',        '{ts escape="sql"}New Petition{/ts}', 'New Petition', 'administer CiviCampaign', '', @nav_campaign_id, '1', NULL, 4 ),
    ( @domainID, 'civicrm/survey/search&reset=1&op=reserve', '{ts escape="sql"}Reserve Voters{/ts}', 'Reserve Voters', 'administer CiviCampaign,manage campaign,reserve campaign contacts', 'OR', @nav_campaign_id, '1', NULL, 5 ),
    ( @domainID, 'civicrm/survey/search&reset=1&op=interview', '{ts escape="sql"}Interview Voters{/ts}', 'Interview Voters', 'administer CiviCampaign,manage campaign,interview campaign contacts', 'OR', @nav_campaign_id, '1', NULL, 6 ),
    ( @domainID, 'civicrm/survey/search&reset=1&op=release', '{ts escape="sql"}Release Voters{/ts}', 'Release Voters', 'administer CiviCampaign,manage campaign,release campaign contacts', 'OR', @nav_campaign_id, '1', NULL, 7 ),
    ( @domainID, 'civicrm/campaign/gotv&reset=1', '{ts escape="sql"}Voter Listing{/ts}', 'Voter Listing', 'administer CiviCampaign,manage campaign', 'OR', @nav_campaign_id, '1', NULL, 8 ),
    ( @domainID, 'civicrm/campaign/vote&reset=1', '{ts escape="sql"}Conduct Survey{/ts}', 'Conduct Survey', 'administer CiviCampaign,manage campaign,reserve campaign contacts,interview campaign contacts', 'OR', @nav_campaign_id, '1', NULL, 9 );

--
--Done w/ campaign db upgrade.
--

-- CRM-6208
insert into civicrm_option_group (name, is_active) values ('system_extensions', 1 );

-- CRM-6907
  ALTER TABLE  `civicrm_event` 
          ADD  `currency` VARCHAR( 3 ) 
CHARACTER SET  utf8 COLLATE utf8_unicode_ci NULL 
      COMMENT  '3 character string, value from config setting or input via user.';

      UPDATE   `civicrm_event` SET `currency` = '{$config->defaultCurrency}';

  ALTER TABLE  `civicrm_contribution_page` 
          ADD  `currency` VARCHAR( 3 ) 
CHARACTER SET  utf8 COLLATE utf8_unicode_ci NOT NULL 
   COMMENT '3  character string, value from config setting or input via user.';

      UPDATE   `civicrm_contribution_page` SET `currency` = '{$config->defaultCurrency}';

-- CRM-6914
ALTER TABLE civicrm_option_value MODIFY COLUMN value varchar(512);

INSERT INTO civicrm_option_group
       	(`name`, {localize field='description'}description{/localize}, `is_active`)
VALUES
	('directory_preferences', {localize}'Directory Preferences'{/localize}     , 1 ),
   	('url_preferences'      , {localize}'URL Preferences'{/localize}   , 1 );

--insert values for Directory and URL preferences
   
SELECT @option_group_id_dirPref := max(id) from civicrm_option_group where name = 'directory_preferences';
SELECT @option_group_id_urlPref := max(id) from civicrm_option_group where name = 'url_preferences';

INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `name`, `value`, `weight`, `is_active`, `domain_id` ) 
VALUES
  (@option_group_id_dirPref, {localize}'Temporary Files'{/localize}  , 'uploadDir'          , '', 1, 1, @domainID ),
  (@option_group_id_dirPref, {localize}'Images'{/localize}           , 'imageUploadDir'     , '', 2, 1, @domainID ),
  (@option_group_id_dirPref, {localize}'Custom Files'{/localize}     , 'customFileUploadDir', '', 3, 1, @domainID ),
  (@option_group_id_dirPref, {localize}'Custom Templates'{/localize} , 'customTemplateDir'  , '', 4, 1, @domainID ),
  (@option_group_id_dirPref, {localize}'Custom PHP'{/localize}       , 'customPHPPathDir'   , '', 5, 1, @domainID ),
  (@option_group_id_dirPref, {localize}'Custom Extensions'{/localize}, 'extensionsDir'      , '', 6, 1, @domainID ),

  (@option_group_id_urlPref, {localize}'CiviCRM Resource URL'{/localize}  , 'userFrameworkResourceURL', '', 1, 1, @domainID ),
  (@option_group_id_urlPref, {localize}'Image Upload URL'{/localize}      , 'imageUploadURL'          , '', 2, 1, @domainID ),
  (@option_group_id_urlPref, {localize}'Custom CiviCRM CSS URL'{/localize}, 'customCSSURL'            , '', 3, 1, @domainID );


-- CRM-6835
ALTER TABLE civicrm_mailing_job ADD COLUMN `job_type` varchar(255) default NULL;
ALTER TABLE civicrm_mailing_job ADD COLUMN `parent_id`  int(10)unsigned default NULL;
ALTER TABLE civicrm_mailing_job ADD COLUMN `job_offset` int(20) default 0;
ALTER TABLE civicrm_mailing_job ADD COLUMN `job_limit` int(20) default 0;
ALTER TABLE civicrm_mailing_job ADD CONSTRAINT parent_id FOREIGN KEY (parent_id) REFERENCES civicrm_mailing_job (id);

-- CRM-6931
SELECT @ogrID       := max(id) from civicrm_option_group where name = 'report_template';
SELECT @max_weight  := max(ROUND(weight)) from civicrm_option_value WHERE option_group_id = @ogrID;
SELECT @caseCompId  := max(id) FROM civicrm_component where name = 'CiviCase';

INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight,{localize field='description'} description{/localize}, is_optgroup,is_reserved, is_active, component_id, visibility_id ) 
VALUES
  (@ogrID, {localize}'{ts escape="sql"}Case Detail Report{/ts}'{/localize}, 'case/detail', 'CRM_Report_Form_Case_Detail', NULL, 0, 0, @max_weight+1, {localize}'{ts escape="sql"}Case Details{/ts}'{/localize}, 0, 0, 1, @caseCompId, NULL);

-- CRM-5718
UPDATE civicrm_contribution_widget 
   SET color_title         = CONCAT( '#', SUBSTRING( color_title, 3 ) ),
       color_button        = CONCAT( '#', SUBSTRING( color_button, 3 ) ),
       color_bar           = CONCAT( '#', SUBSTRING( color_bar, 3 ) ),
       color_main_text     = CONCAT( '#', SUBSTRING( color_main_text, 3 ) ),
       color_main          = CONCAT( '#', SUBSTRING( color_main, 3 ) ),
       color_main_bg       = CONCAT( '#', SUBSTRING( color_main_bg, 3 ) ),
       color_bg            = CONCAT( '#', SUBSTRING( color_bg, 3 ) ),
       color_about_link    = CONCAT( '#', SUBSTRING( color_about_link, 3 ) ),
       color_homepage_link = CONCAT( '#', SUBSTRING( color_homepage_link, 3 ) );
 

--CRM-4572

ALTER TABLE civicrm_address ADD COLUMN master_id INT(10) unsigned default NULL COMMENT 'FK to Address ID';
ALTER TABLE civicrm_address ADD CONSTRAINT FK_civicrm_address_master_id  FOREIGN KEY (master_id) REFERENCES civicrm_address (id) ON DELETE SET NULL;

UPDATE civicrm_address add1
INNER JOIN civicrm_contact c1 ON ( c1.id = add1.contact_id AND c1.mail_to_household_id IS NOT NULL )
INNER JOIN civicrm_address add2 ON ( c1.mail_to_household_id = add2.contact_id AND add2.is_primary = 1 )
SET add1.master_id = add2.id;

UPDATE civicrm_contact SET mail_to_household_id = NULL;

ALTER TABLE civicrm_contact DROP  FOREIGN KEY FK_civicrm_contact_mail_to_household_id;
ALTER TABLE civicrm_contact DROP mail_to_household_id;

-- added shared address profile.
INSERT INTO civicrm_uf_group
    (name, group_type, {localize field='title'}title{/localize}, is_reserved ) VALUES
    ('shared_address', 'Contact',  {localize}'Shared Address'{/localize}, 1 );
    
SELECT @uf_group_id_sharedAddress   := max(id) from civicrm_uf_group where name = 'shared_address';

INSERT INTO civicrm_uf_join
   (is_active,module,entity_table,entity_id,weight,uf_group_id) VALUES
   (1, 'Profile', NULL, NULL, 7, @uf_group_id_sharedAddress );
   
INSERT INTO civicrm_uf_field
   (uf_group_id, field_name, is_required, is_reserved, weight, visibility, in_selector, is_searchable, location_type_id, {localize field='label'}label{/localize}, field_type, {localize field='help_post'}help_post{/localize}, phone_type_id ) VALUES
   (@uf_group_id_sharedAddress, 'street_address',  0, 0, 1, 'User and User Admin Only',  0, 0, 1, {localize}'Street Address (Home)'{/localize},     'Contact',     {localize}NULL{/localize},  NULL),
   (@uf_group_id_sharedAddress, 'city',            0, 0, 2, 'User and User Admin Only',  0, 0, 1, {localize}'City (Home)'{/localize},        'Contact',     {localize}NULL{/localize},  NULL),
   (@uf_group_id_sharedAddress, 'postal_code',     0, 0, 3, 'User and User Admin Only',  0, 0, 1, {localize}'Postal Code (Home)'{/localize}, 'Contact',     {localize}NULL{/localize},  NULL),
   (@uf_group_id_sharedAddress, 'country',         0, 0, 4, 'Public Pages and Listings', 0, 1, 1, {localize}'Country (Home)'{/localize},     'Contact',     {localize}NULL{/localize},  NULL),
   (@uf_group_id_sharedAddress, 'state_province',  0, 0, 5, 'Public Pages and Listings', 1, 1, 1, {localize}'State (Home)'{/localize},       'Contact',     {localize}NULL{/localize},  NULL);

-- CRM-6894
CREATE TABLE `civicrm_batch` ( 
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Address ID.',
  `name` varchar(64) DEFAULT NULL COMMENT 'Variable name/programmatic handle for this batch.',  
  `label` varchar(64) DEFAULT NULL COMMENT 'Friendly Name.',     
  `description` text COMMENT 'Description of this batch set.',                  
  `created_id` int(10) unsigned default NULL COMMENT 'FK to Contact ID',
  `created_date` datetime default NULL COMMENT 'When was this item created',
  `modified_id` int(10) unsigned default NULL COMMENT 'FK to Contact ID',
  `modified_date` datetime default NULL COMMENT 'When was this item created',
  PRIMARY KEY ( `id` ),
  CONSTRAINT FK_civicrm_batch_created_id FOREIGN KEY ( created_id ) REFERENCES civicrm_contact( id ) ON DELETE SET NULL,
  CONSTRAINT FK_civicrm_batch_modified_id FOREIGN KEY ( modified_id ) REFERENCES civicrm_contact( id ) ON DELETE SET NULL
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `civicrm_entity_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'primary key',
  `entity_table` varchar(64) DEFAULT NULL COMMENT 'physical tablename for entity being joined to file, e.g. civicrm_contact',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'FK to entity table specified in entity_table column.',
  `batch_id` int(10) unsigned NOT NULL COMMENT 'FK to civicrm_batch',
  PRIMARY KEY ( id ),
  INDEX index_entity ( entity_table, entity_id ),
  UNIQUE INDEX UI_batch_entity ( batch_id, entity_id, entity_table ),
  CONSTRAINT FK_civicrm_entity_batch_batch_id FOREIGN KEY ( batch_id ) REFERENCES civicrm_batch ( id ) ON DELETE CASCADE
 )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- CRM-3702
CREATE TABLE `civicrm_dedupe_exception` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique dedupe exception id.',
  `contact_id1` int(10) unsigned default NULL COMMENT 'FK to Contact ID',
  `contact_id2` int(10) unsigned default NULL COMMENT 'FK to Contact ID',
  PRIMARY KEY ( id ),
  UNIQUE INDEX UI_contact_id1_contact_id2 (`contact_id1`, `contact_id2`),
  CONSTRAINT FK_civicrm_dedupe_exception_contact_id1 FOREIGN KEY (`contact_id1`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_dedupe_exception_contact_id2 FOREIGN KEY (`contact_id2`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

