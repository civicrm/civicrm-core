    SELECT @domain_id := min(id) FROM civicrm_domain;

    -- CRM-4048
    -- modify visibility of civicrm_group
    ALTER TABLE `civicrm_group` 
        MODIFY `visibility` enum('User and User Admin Only','Public User Pages','Public User Pages and Listings', 'Public Pages') collate utf8_unicode_ci default 'User and User Admin Only' COMMENT 'In what context(s) is this field visible.';

    UPDATE civicrm_group SET visibility = 'Public Pages' WHERE  visibility IN ('Public User Pages', 'Public User Pages and Listings');

    ALTER TABLE `civicrm_group` 
        MODIFY `visibility` enum('User and User Admin Only', 'Public Pages') collate utf8_unicode_ci default 'User and User Admin Only' COMMENT 'In what context(s) is this field visible.';

    -- Modify visibility of civicrm_uf_field
    ALTER TABLE `civicrm_uf_field` 
        MODIFY `visibility` enum('User and User Admin Only','Public User Pages','Public User Pages and Listings', 'Public Pages', 'Public Pages and Listings') collate utf8_unicode_ci default 'User and User Admin Only' COMMENT 'In what context(s) is this field visible.';

    UPDATE civicrm_uf_field SET visibility = 'Public Pages'              WHERE  visibility = 'Public User Pages';
    UPDATE civicrm_uf_field SET visibility = 'Public Pages and Listings' WHERE  visibility = 'Public User Pages and Listings';

    ALTER TABLE `civicrm_uf_field` 
        MODIFY `visibility` enum('User and User Admin Only', 'Public Pages', 'Public Pages and Listings') collate utf8_unicode_ci default 'User and User Admin Only' COMMENT 'In what context(s) is this field visible.';


    -- CRM-3336
    -- Add two label_a_b and label_b_a column in civicrm_relationship_type table 
    ALTER TABLE `civicrm_relationship_type`
        ADD `label_a_b` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'label for relationship of contact_a to contact_b.' AFTER `name_a_b`,
        ADD `label_b_a` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'Optional label for relationship of contact_b to contact_a.' AFTER `name_b_a`;

    -- Copy value from name_a_b to label_a_b and name_b_a to label_b_a column in civicrm_relationship_type.
    UPDATE civicrm_relationship_type
        SET  civicrm_relationship_type.label_a_b = civicrm_relationship_type.name_a_b, civicrm_relationship_type.label_b_a = civicrm_relationship_type.name_b_a;

    -- Alter comment of name_a_b and name_b_a column in civicrm_relationship_type table 
    ALTER TABLE `civicrm_relationship_type`
        CHANGE `name_a_b` `name_a_b` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'name for relationship of contact_a to contact_b.' ,
        CHANGE `name_b_a` `name_b_a` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Optional name for relationship of contact_b to contact_a.';

    -- CRM-3140
    ALTER TABLE `civicrm_mapping_field`
        ADD `im_provider_id` int(10) unsigned default NULL COMMENT 'Which type of IM Provider does this name belong' AFTER `phone_type_id`;


    -- migrate participant status types, CRM-4321
  
    -- /*******************************************************
    -- * civicrm_participant_status_type    
    -- * various types of CiviEvent participant statuses    
    -- *******************************************************/
    BEGIN;
    CREATE TABLE civicrm_participant_status_type (
        id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'unique participant status type id',
        name varchar(64)    COMMENT 'non-localized name of the status type',
        {localize field='label'}label varchar(255)    COMMENT 'localized label for display of this status type'{/localize},
        class enum('Positive', 'Pending', 'Waiting', 'Negative')    COMMENT 'the general group of status type this one belongs to',
        is_reserved tinyint    COMMENT 'whether this is a status type required by the system',
        is_active tinyint   DEFAULT 1 COMMENT 'whether this status type is active',
        is_counted tinyint    COMMENT 'whether this status type is counted against event size limit',
        weight int unsigned NOT NULL   COMMENT 'controls sort order',
        visibility_id int unsigned    COMMENT 'whether the status type is visible to the public, an implicit foreign key to option_value.value related to the `visibility` option_group',
        PRIMARY KEY ( id )
    )  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

    SELECT @ps_ogid := id FROM civicrm_option_group WHERE name = 'participant_status';
    INSERT INTO civicrm_participant_status_type
        (id, name, {localize field='label'}label{/localize}, is_reserved, is_active, is_counted, weight, visibility_id)
    SELECT value, name, {localize field='label'}label{/localize}, is_reserved, is_active, filter, weight, visibility_id
    FROM civicrm_option_value WHERE option_group_id = @ps_ogid;

    UPDATE civicrm_participant_status_type
	SET class = CASE name
                        WHEN 'Registered' THEN 'Positive'
                        WHEN 'Attended'   THEN 'Positive'
                        WHEN 'No-show'    THEN 'Negative'
                        WHEN 'Cancelled'  THEN 'Negative'
                        ELSE 'Pending'
                    END;
                    
    UPDATE civicrm_participant_status_type
        SET name = 'Pending from pay later', {localize field='label'}label = 'Pending from pay later'{/localize}
    WHERE name = 'Pending';

    INSERT INTO civicrm_participant_status_type
        (name,                                  {localize field='label'}label{/localize},                    class,      is_reserved, is_active, is_counted, weight, visibility_id)
    VALUES
        ('Pending from incomplete transaction', {localize}'Pending from incomplete transaction'{/localize}, 'Pending',  1,           1,         1,          6,      2            ),
        ('On waitlist',                         {localize}'On waitlist'{/localize},                         'Waiting',  1,           0,         0,          7,      2            ),
        ('Awaiting approval',                   {localize}'Awaiting approval'{/localize},                   'Waiting',  1,           0,         1,          8,      2            ),
        ('Pending from waitlist',               {localize}'Pending from waitlist'{/localize},               'Pending',  1,           0,         1,          9,      2            ),
        ('Pending from approval',               {localize}'Pending from approval'{/localize},               'Pending',  1,           0,         1,          10,     2            ),
        ('Rejected',                            {localize}'Rejected'{/localize},                            'Negative', 1,           0,         0,          11,     2            ),
        ('Expired',                             {localize}'Expired'{/localize},                             'Negative', 1,           1,         0,          12,     2            );

    -- CRM-4321 migration: Pending from pay later + false is_pay_later ==> Pending from incomplete transaction
    SELECT @ps_ppl := id FROM civicrm_participant_status_type WHERE name = 'Pending from pay later';
    SELECT @ps_pit := id FROM civicrm_participant_status_type WHERE name = 'Pending from incomplete transaction';
    UPDATE civicrm_participant SET status_id = @ps_pit WHERE status_id = @ps_ppl AND is_pay_later = 0;

    DELETE FROM civicrm_option_value WHERE option_group_id = @ps_ogid;
    DELETE FROM civicrm_option_group WHERE              id = @ps_ogid;
 
    UPDATE civicrm_participant SET status_id = 1 WHERE status_id IS NULL;
    ALTER TABLE `civicrm_participant`
        CHANGE `status_id` `status_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Participant status ID. FK to civicrm_participant_status_type. Default of 1 should map to status = Registered.',
        ADD CONSTRAINT FK_civicrm_participant_status_id FOREIGN KEY (status_id) REFERENCES civicrm_participant_status_type (id);

    COMMIT;

    -- Add is_reserved, name column to civicrm_uf_group table.
    ALTER TABLE `civicrm_uf_group` 
        ADD `is_reserved` TINYINT( 4 ) NULL DEFAULT NULL COMMENT 'Is this group reserved for use by some other CiviCRM functionality?',
        ADD `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'Name of the UF group for directly addressing it in the codebase';

    -- Add is_reserved column to civicrm_uf_field.
    ALTER TABLE `civicrm_uf_field` 
        ADD `is_reserved` TINYINT( 4 ) NULL DEFAULT NULL COMMENT 'Is this group reserved for use by some other CiviCRM functionality?';

    -- add a profile for CRM-4323
    BEGIN;
        INSERT INTO civicrm_uf_group
            (name,                 group_type,    {localize field='title'}title{/localize},  is_reserved)
        VALUES
            ('participant_status', 'Participant', {localize}'Participant Status'{/localize}, 1);
        SELECT @ufgid := id FROM civicrm_uf_group WHERE name = 'participant_status';
        INSERT INTO civicrm_uf_field
            (uf_group_id, field_name,              is_required, is_reserved, {localize field='label'}label{/localize},  field_type)
        VALUES
            (@ufgid,      'participant_status_id', 1,           1,           {localize}'Participant Status'{/localize}, 'Participant');
    COMMIT;

    -- CRM-4407
    ALTER TABLE `civicrm_preferences` ADD `navigation` TEXT NULL AFTER `mailing_backend` ;

    -- CRM-3553
    -- Activity Type for bulk email
    -- CRM-4480
    -- Activity Type for case role assignment

    SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
    SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
    SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';
    SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;

    INSERT INTO civicrm_option_value
        (option_group_id,                {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, value,                           name,               weight,                        filter, component_id)
    VALUES
        (@option_group_id_activity_type, {localize}'Bulk Email'{/localize},        {localize}'Bulk Email Sent.'{/localize},              (SELECT @max_val := @max_val+1), 'Bulk Email',       (SELECT @max_wt := @max_wt+1), 1,      NULL),
        (@option_group_id_activity_type, {localize}'Assign Case Role'{/localize},  {localize}''{/localize},                              (SELECT @max_val := @max_val+2), 'Assign Case Role', (SELECT @max_wt := @max_wt+2), 0,      @caseCompId),
        (@option_group_id_activity_type, {localize}'Remove Case Role'{/localize},  {localize}''{/localize},                              (SELECT @max_val := @max_val+3), 'Remove Case Role', (SELECT @max_wt := @max_wt+3), 0,      @caseCompId);

    -- CRM-5333
    -- Drop unique indexes of activity_target and activity_assignment
 
    ALTER TABLE  civicrm_activity_assignment 
    DROP INDEX `UI_activity_assignee_contact_id` ,
    ADD  INDEX `UI_activity_assignee_contact_id` (`assignee_contact_id`,`activity_id`);

    ALTER TABLE  civicrm_activity_target 
    DROP INDEX `UI_activity_target_contact_id` ,
    ADD INDEX `UI_activity_target_contact_id` (`target_contact_id`,`activity_id`);

    -- delete unnecessary activities
    SELECT @bulkEmailID := op.value from civicrm_option_value op where op.name = 'Bulk Email' and op.option_group_id  = @option_group_id_activity_type;

    UPDATE civicrm_activity ca
        SET ca.activity_type_id = @bulkEmailID
    WHERE   ca.activity_type_id = 3
            AND ca.source_record_id IS NOT NULL
            AND ca.id NOT IN ( SELECT cca.activity_id FROM civicrm_case_activity cca );

    -- CRM-4478  CRM-4569
    INSERT INTO civicrm_option_group
        (name,              {localize field='description'}description{/localize}, is_reserved, is_active)
    VALUES 
        ('priority',        {localize}'Priority'{/localize},                      0,           1),
        ('redaction_rule',  {localize}'Redaction Rule'{/localize},                0,           1),
        ('email_greeting',  {localize}'Email Greeting Type'{/localize},           0,           1),
        ('postal_greeting', {localize}'Postal Greeting Type'{/localize},          0,           1),
        ('addressee',       {localize}'Addressee Type'{/localize},                0,           1);
    
    SELECT @og_id_pr := id FROM civicrm_option_group WHERE name = 'priority';
    SELECT @og_id_rr := id FROM civicrm_option_group WHERE name = 'redaction_rule';
    SELECT @og_id_rt := id FROM civicrm_option_group WHERE name = 'report_template';
    INSERT INTO civicrm_option_value
        (option_group_id, {localize field='label'}label{/localize},                                          value,   name,     filter, weight, is_active)
    VALUES    
        (@og_id_pr,       {localize}'Urgent'{/localize},                                                     1,       'Urgent', 0,      1,      1),
        (@og_id_pr,       {localize}'Normal'{/localize},                                                     2,       'Normal', 0,      2,      1),
        (@og_id_pr,       {localize}'Low'{/localize},                                                        3,       'Low',    0,      3,      1),
                                                                                                                                               
        (@og_id_rr,       {localize}'Vancouver'{/localize},                                                  'city_', NULL,     0,      1,      1),
        (@og_id_rr,       {localize}'{literal}/(19|20)(\\d{2})-(\\d{1,2})-(\\d{1,2})/{/literal}'{/localize}, 'date_', NULL,     1,      2,      1);


    -- CRM-4461
    -- Add a new custom html type advanced multi-select
    -- CRM-4679
    -- Add a new custom data type Auto-Complete & html type Contact Reference
    ALTER TABLE `civicrm_custom_field` 
        MODIFY `data_type` enum ('String', 'Int', 'Float', 'Money', 'Memo', 'Date', 'Boolean', 'StateProvince', 'Country', 'File', 'Link', 'ContactReference')NOT NULL COMMENT 'Controls location of data storage in extended_data table.',
        MODIFY `html_type` enum ('Text', 'TextArea', 'Select', 'Multi-Select', 'AdvMulti-Select', 'Radio', 'CheckBox', 'Select Date', 'Select State/Province', 'Select Country', 'Multi-Select Country', 'Multi-Select State/Province', 'File', 'Link', 'RichTextEditor', 'Autocomplete-Select')NOT NULL COMMENT 'HTML types plus several built-in extended types.';

    -- CRM-4407
    -- Add civicrm_navigation table for CiviCRM Menu
    CREATE TABLE civicrm_navigation (
        id int unsigned NOT NULL AUTO_INCREMENT  ,
        domain_id int unsigned NOT NULL   COMMENT 'Which Domain is this navigation item for',
        label varchar(255)    COMMENT 'Navigation Title',
        name varchar(255)    COMMENT 'Internal Name',
        url varchar(255)    COMMENT 'url in case of custom navigation link',
        permission varchar(255)    COMMENT 'Permission for menu item',
        permission_operator varchar(3)    COMMENT 'Permission Operator',
        parent_id int unsigned    COMMENT 'Parent navigation item, used for grouping',
        is_active tinyint    COMMENT 'Is this navigation item active?',
        has_separator tinyint    COMMENT 'If separator needs to be added after this menu item',
        weight int    COMMENT 'Ordering of the navigation items in various blocks.' 
        ,
        PRIMARY KEY ( id )
        ,      
        CONSTRAINT FK_civicrm_navigation_domain_id FOREIGN KEY (domain_id) REFERENCES civicrm_domain(id) ,      
        CONSTRAINT FK_civicrm_navigation_parent_id FOREIGN KEY (parent_id) REFERENCES civicrm_navigation(id) ON DELETE CASCADE  
    )  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
    
    -- Insert default menu to table
    INSERT INTO civicrm_navigation
        ( id, domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
    VALUES
        ( 1,  @domain_id, NULL,                                                 '{ts escape="sql"}Search...{/ts}', 'Search...', NULL, '', NULL, '1', NULL, 1 ), 
        ( 2,  @domain_id, 'civicrm/contact/search&reset=1',                     '{ts escape="sql"}Find Contacts{/ts}', 'Find Contacts', NULL, '', '1', '1', NULL, 1 ), 
        ( 3,  @domain_id, 'civicrm/contact/search/advanced&reset=1',            '{ts escape="sql"}Find Contacts - Advanced Search{/ts}', 'Find Contacts - Advanced Search', NULL, '', '1', '1', NULL, 2 ), 
        ( 4,  @domain_id, 'civicrm/contact/search/custom&csid=15&reset=1',      '{ts escape="sql"}Full-text Search{/ts}', 'Full-text Search', NULL, '', '1', '1', NULL, 3 ), 
        ( 5,  @domain_id, 'civicrm/contact/search/builder&reset=1',             '{ts escape="sql"}Search Builder{/ts}', 'Search Builder', NULL, '', '1', '1', '1', 4 ), 
        ( 6,  @domain_id, 'civicrm/case/search&reset=1',                        '{ts escape="sql"}Find Cases{/ts}', 'Find Cases', 'access CiviCase', '', '1', '1', NULL, 5 ), 
        ( 7,  @domain_id, 'civicrm/contribute/search&reset=1',                  '{ts escape="sql"}Find Contributions{/ts}', 'Find Contributions', 'access CiviContribute', '', '1', '1', NULL, 6 ), 
        ( 8,  @domain_id, 'civicrm/mailing&reset=1',                            '{ts escape="sql"}Find Mailings{/ts}', 'Find Mailings', 'access CiviMail', '', '1', '1', NULL, 7 ), 
        ( 9,  @domain_id, 'civicrm/member/search&reset=1',                      '{ts escape="sql"}Find Members{/ts}', 'Find Members', 'access CiviMember', '', '1', '1', NULL, 8 ), 
        ( 10, @domain_id, 'civicrm/event/search&reset=1',                       '{ts escape="sql"}Find Participants{/ts}', 'Find Participants',  'access CiviEvent', '', '1', '1', NULL, 9 ), 
        ( 11, @domain_id, 'civicrm/pledge/search&reset=1',                      '{ts escape="sql"}Find Pledges{/ts}', 'Find Pledges', 'access CiviPledge', '', '1', '1', 1, 10 ), 
        
        ( 12, @domain_id, 'civicrm/contact/search/custom/list&reset=1',         '{ts escape="sql"}Custom Searches...{/ts}', 'Custom Searches...', NULL, '', '1', '1', NULL, 11 ), 
        ( 13, @domain_id, 'civicrm/contact/search/custom&reset=1&csid=8',       '{ts escape="sql"}Activity Search{/ts}', 'Activity Search', NULL, '', '12', '1', NULL, 1 ), 
        ( 14, @domain_id, 'civicrm/contact/search/custom&reset=1&csid=11',      '{ts escape="sql"}Contacts by Date Added{/ts}', 'Contacts by Date Added', NULL, '', '12', '1', NULL, 2 ), 
        ( 15, @domain_id, 'civicrm/contact/search/custom&reset=1&csid=2',       '{ts escape="sql"}Contributors by Aggregate Totals{/ts}', 'Contributors by Aggregate Totals', NULL, '', '12', '1', NULL, 3 ), 
        ( 16, @domain_id, 'civicrm/contact/search/custom&reset=1&csid=6',       '{ts escape="sql"}Proximity Search{/ts}', 'Proximity Search', NULL, '', '12', '1', NULL, 4 ), 
        
        ( 17, @domain_id, NULL,                                                 '{ts escape="sql"}Contacts{/ts}', 'Contacts', NULL, '', NULL, '1', NULL, 3 ), 
        ( 18, @domain_id, 'civicrm/contact/add&reset=1&ct=Individual',          '{ts escape="sql"}New Individual{/ts}', 'New Individual', NULL, '', '17', '1', NULL, 1 ), 
        ( 19, @domain_id, 'civicrm/contact/add&reset=1&ct=Household',           '{ts escape="sql"}New Household{/ts}', 'New Household', NULL, '', '17', '1', NULL, 2 ), 
        ( 20, @domain_id, 'civicrm/contact/add&reset=1&ct=Organization',        '{ts escape="sql"}New Organization{/ts}', 'New Organization', NULL, '', '17', '1', 1, 3 ), 
        ( 21, @domain_id, 'civicrm/activity&reset=1&action=add&context=standalone', '{ts escape="sql"}New Activity{/ts}', 'New Activity', NULL, '', '17', '1', NULL, 4 ), 
        ( 22, @domain_id, 'civicrm/contact/view/activity&atype=3&action=add&reset=1&context=standalone', '{ts escape="sql"}New Email{/ts}', 'New Email', NULL, '', '17', '1', '1', 5 ), 
        ( 23, @domain_id, 'civicrm/import/contact&reset=1',                     '{ts escape="sql"}Import Contacts{/ts}', 'Import Contacts', NULL, '', '17', '1', NULL, 6 ), 
        ( 24, @domain_id, 'civicrm/import/activity&reset=1',                    '{ts escape="sql"}Import Activities{/ts}', 'Import Activities', NULL, '', '17', '1', '1', 7 ), 
        ( 25, @domain_id, 'civicrm/group/add&reset=1',                          '{ts escape="sql"}New Group{/ts}', 'New Group', NULL, '', '17', '1', NULL, 8 ), 
        ( 26, @domain_id, 'civicrm/group&reset=1',                              '{ts escape="sql"}Manage Groups{/ts}', 'Manage Groups', NULL, '', '17', '1', '1', 9 ), 
        ( 27, @domain_id, 'civicrm/admin/tag&reset=1&action=add',               '{ts escape="sql"}New Tag{/ts}', 'New Tag', NULL, '', '17', '1', NULL, 10 ), 
        ( 28, @domain_id, 'civicrm/admin/tag&reset=1',                          '{ts escape="sql"}Manage Tags (Categories){/ts}', 'Manage Tags (Categories)', NULL, '', '17', '1', NULL, 11 ), 

        ( 29, @domain_id, NULL,                                                 '{ts escape="sql"}Contributions{/ts}', 'Contributions', 'access CiviContribute', '', NULL, '1', NULL, 4 ), 
        ( 30, @domain_id, 'civicrm/contribute&reset=1',                         '{ts escape="sql"}Dashboard{/ts}', 'Dashboard', NULL, '', '29', '1', NULL, 1 ), 
        ( 31, @domain_id, 'civicrm/contact/view/contribution&reset=1&action=add&context=standalone', '{ts escape="sql"}New Contribution{/ts}', 'New Contribution', NULL, '', '29', '1', NULL, 2 ), 
        ( 32, @domain_id, 'civicrm/contribute/search&reset=1',                  '{ts escape="sql"}Find Contributions{/ts}', 'Find Contributions', NULL, '', '29', '1', NULL, 3 ), 
        ( 33, @domain_id, 'civicrm/contribute/import&reset=1',                  '{ts escape="sql"}Import Contributions{/ts}', 'Import Contributions', NULL, '', '29', '1', '1', 4 ),
        
        ( 34, @domain_id, NULL,                                                 '{ts escape="sql"}Pledges{/ts}', 'Pledges', 'access CiviPledge', '', 29, '1', 1, 5 ), 
        ( 35, @domain_id, 'civicrm/pledge&reset=1',                             '{ts escape="sql"}Dashboard{/ts}', 'Dashboard', NULL, '', '34', '1', NULL, 1 ), 
        ( 36, @domain_id, 'civicrm/pledge/search&reset=1',                      '{ts escape="sql"}Find Pledges{/ts}', 'Find Pledges', NULL, '', '34', '1', NULL, 2 ), 
        ( 37, @domain_id, 'civicrm/admin/contribute&reset=1&action=add',        '{ts escape="sql"}New Contribution Page{/ts}', 'New Contribution Page', NULL, '', '29', '1', NULL, 6 ), 
        ( 38, @domain_id, 'civicrm/admin/contribute&reset=1',                   '{ts escape="sql"}Manage Contribution Pages{/ts}', 'Manage Contribution Pages', NULL, '', '29', '1', '1', 7 ), 
        ( 39, @domain_id, 'civicrm/admin/pcp&reset=1',                          '{ts escape="sql"}Personal Campaign Pages{/ts}', 'Personal Campaign Pages', NULL, '', '29', '1', NULL, 8 ), 
        ( 40, @domain_id, 'civicrm/admin/contribute/managePremiums&reset=1',    '{ts escape="sql"}Premiums (Thank-you Gifts){/ts}', 'Premiums', NULL, '', '29', '1', NULL, 9	 ), 
        
        ( 41, @domain_id, NULL,                                                 '{ts escape="sql"}Events{/ts}', 'Events', 'access CiviEvent', '', NULL, '1', NULL, 5 ), 
        ( 42, @domain_id, 'civicrm/event&reset=1',                              '{ts escape="sql"}Dashboard{/ts}', 'CiviEvent Dashboard', NULL, '', '41', '1', NULL, 1 ), 
        ( 43, @domain_id, 'civicrm/contact/view/participant&reset=1&action=add&context=standalone', '{ts escape="sql"}Register Event Participant{/ts}', 'Register Event Participant', NULL, '', '41', '1', NULL, 2 ), 
        ( 44, @domain_id, 'civicrm/event/search&reset=1',                       '{ts escape="sql"}Find Participants{/ts}', 'Find Participants', NULL, '', '41', '1', NULL, 3 ), 
        ( 45, @domain_id, 'civicrm/event/import&reset=1',                       '{ts escape="sql"}Import Participants{/ts}', 'Import Participants', NULL, '', '41', '1', '1', 4 ), 
        ( 46, @domain_id, 'civicrm/event/add&reset=1&action=add',               '{ts escape="sql"}New Event{/ts}', 'New Event', NULL, '', '41', '1', NULL, 5 ), 
        ( 47, @domain_id, 'civicrm/event/manage&reset=1',                       '{ts escape="sql"}Manage Events{/ts}', 'Manage Events', NULL, '', '41', '1', 1, 6 ), 
        ( 48, @domain_id, 'civicrm/admin/eventTemplate&reset=1',                '{ts escape="sql"}Event Templates{/ts}', 'Event Templates', 'access CiviEvent,administer CiviCRM', '', '41', '1', 1, 7 ), 
        ( 49, @domain_id, 'civicrm/admin/price&reset=1&action=add',             '{ts escape="sql"}New Price Set{/ts}', 'New Price Set', NULL, '', '41', '1', NULL, 8 ), 
        ( 50, @domain_id, 'civicrm/event/price&reset=1',                        '{ts escape="sql"}Manage Price Sets{/ts}', 'Manage Price Sets', NULL, '', '41', '1', NULL, 9 ),
        
        ( 51, @domain_id, NULL,                                                 '{ts escape="sql"}Mailings{/ts}', 'Mailings', 'access CiviMail', '', NULL, '1', NULL, 6 ), 
        ( 52, @domain_id, 'civicrm/mailing/send&reset=1',                       '{ts escape="sql"}New Mailing{/ts}', 'New Mailing', NULL, '', '51', '1', NULL, 1 ), 
        ( 53, @domain_id, 'civicrm/mailing/browse/unscheduled&reset=1&scheduled=false', '{ts escape="sql"}Draft and Unscheduled Mailings{/ts}', 'Draft and Unscheduled Mailings', NULL, '', '51', '1', NULL, 2 ), 
        ( 54, @domain_id, 'civicrm/mailing/browse/scheduled&reset=1&scheduled=true', '{ts escape="sql"}Scheduled and Sent Mailings{/ts}', 'Scheduled and Sent Mailings', NULL, '', '51', '1', NULL, 3 ), 
        ( 55, @domain_id, 'civicrm/mailing/browse/archived&reset=1',            '{ts escape="sql"}Archived Mailings{/ts}', 'Archived Mailings', NULL, '', '51', '1', 1, 4 ), 
        ( 56, @domain_id, 'civicrm/admin/component&reset=1',                    '{ts escape="sql"}Headers, Footers, and Automated Messages{/ts}', 'Headers, Footers, and Automated Messages', NULL, '', '51', '1', NULL, 5 ), 
        ( 57, @domain_id, 'civicrm/admin/messageTemplates&reset=1',             '{ts escape="sql"}Message Templates{/ts}', 'Message Templates', NULL, '', '51', '1', NULL, 6 ), 
        ( 58, @domain_id, 'civicrm/admin/options/from_email&group=from_email_address&reset=1', '{ts escape="sql"}From Email Addresses{/ts}', 'From Email Addresses', NULL, '', '51', '1', NULL, 7 ), 
        
        ( 59, @domain_id, NULL,                                                 '{ts escape="sql"}Memberships{/ts}', 'Memberships', 'access CiviMember', '', NULL, '1', NULL, 7 ), 
        ( 60, @domain_id, 'civicrm/member&reset=1',                             '{ts escape="sql"}Dashboard{/ts}', 'Dashboard', NULL, '', '59', '1', NULL, 1 ), 
        ( 61, @domain_id, 'civicrm/contact/view/membership&reset=1&action=add&context=standalone', '{ts escape="sql"}New Membership{/ts}', 'New Membership', NULL, '', '59', '1', NULL, 2 ), 
        ( 62, @domain_id, 'civicrm/member/search&reset=1',                      '{ts escape="sql"}Find Members{/ts}', 'Find Members', NULL, '', '59', '1', NULL, 3 ), 
        ( 63, @domain_id, 'civicrm/member/import&reset=1',                      '{ts escape="sql"}Import Members{/ts}', 'Import Members', NULL, '', '59', '1', NULL, 4 ), 
        
        ( 64, @domain_id, NULL,                                                 '{ts escape="sql"}Other{/ts}', 'Other', 'access CiviGrant,access CiviCase', 'OR', NULL, '1', NULL, 8 ), 
        ( 65, @domain_id, NULL,                                                 '{ts escape="sql"}Cases{/ts}', 'Cases', 'access CiviCase', '', '64', '1', NULL, 1 ), 
        ( 66, @domain_id, 'civicrm/case&reset=1',                               '{ts escape="sql"}Dashboard{/ts}', 'Dashboard', NULL, '', '65', '1', NULL, 1 ), 
        ( 67, @domain_id, 'civicrm/contact/view/case&reset=1&action=add&atype=13&context=standalone', '{ts escape="sql"}New Case{/ts}', 'New Case', NULL, '', '65', '1', NULL, 2 ), 
        ( 68, @domain_id, 'civicrm/case/search&reset=1',                        '{ts escape="sql"}Find Cases{/ts}', 'Find Cases', NULL, '', '65', '1', 1, 3 ), 
        
        ( 69, @domain_id, NULL,                                                 '{ts escape="sql"}Grants{/ts}', 'Grants', 'access CiviGrant', '', '64', '1', NULL, 2 ),
        ( 70, @domain_id, 'civicrm/grant&reset=1',                              '{ts escape="sql"}Dashboard{/ts}', 'Dashboard', NULL, '', '69', '1', NULL, 1 ), 
        ( 71, @domain_id, 'civicrm/contact/view/grant&reset=1&action=add&context=standalone', '{ts escape="sql"}New Grant{/ts}', 'New Grant', NULL, '', '69', '1', NULL, 2 ), 
        ( 72, @domain_id, 'civicrm/grant/search&reset=1',                       '{ts escape="sql"}Find Grants{/ts}', 'Find Grants', NULL, '', '69', '1', 1, 3 ), 
        
        ( 73, @domain_id, NULL,                                                 '{ts escape="sql"}Administer{/ts}', 'Administer', 'administer CiviCRM', '', NULL, '1', NULL, 9 ), 
        ( 74, @domain_id, 'civicrm/admin&reset=1',                              '{ts escape="sql"}Administration Console{/ts}', 'Administration Console', NULL, '', '73', '1', NULL, 1 ), 
        
        ( 75, @domain_id, NULL,                                                 '{ts escape="sql"}Customize{/ts}', 'Customize', NULL, '', '73', '1', NULL, 2 ), 
        ( 76, @domain_id, 'civicrm/admin/custom/group&reset=1',                 '{ts escape="sql"}Custom Data{/ts}', 'Custom Data', NULL, '', '75', '1', NULL, 1 ), 
        ( 77, @domain_id, 'civicrm/admin/uf/group&reset=1',                     '{ts escape="sql"}CiviCRM Profile{/ts}', 'CiviCRM Profile', NULL, '', '75', '1', NULL, 2 ), 
        ( 78, @domain_id, 'civicrm/admin/menu&reset=1',                         '{ts escape="sql"}Navigation Menu{/ts}', 'Navigation Menu', NULL, '', '75', '1', NULL, 3 ), 
        ( 79, @domain_id, 'civicrm/admin/options/custom_search&reset=1&group=custom_search', '{ts escape="sql"}Manage Custom Searches{/ts}', 'Manage Custom Searches', NULL, '', '75', '1', NULL, 4 ), 
        
        ( 80, @domain_id, NULL,                                                 '{ts escape="sql"}Configure{/ts}', 'Configure', NULL, '', '73', '1', NULL, 3 ), 
        ( 81, @domain_id, 'civicrm/admin/configtask&reset=1',                   '{ts escape="sql"}Configuration Checklist{/ts}', 'Configuration Checklist', NULL, '', '80', '1', NULL, 1 ), 
        
        ( 82, @domain_id, 'civicrm/admin/setting&reset=1',                      '{ts escape="sql"}Global Settings{/ts}', 'Global Settings', NULL, '', '80', '1', NULL, 2 ), 
        ( 83, @domain_id, 'civicrm/admin/setting/component&reset=1',            '{ts escape="sql"}Enable CiviCRM Components{/ts}', 'Enable Components', NULL, '', '82', '1', NULL, 1 ), 
        ( 84, @domain_id, 'civicrm/admin/setting/preferences/display&reset=1',  '{ts escape="sql"}Site Preferences (screen and form configuration){/ts}', 'Site Preferences', NULL, '', '82', '1', NULL, 2 ), 
        ( 85, @domain_id, 'civicrm/admin/setting/path&reset=1',                 '{ts escape="sql"}Directories{/ts}', 'Directories', NULL, '', '82', '1', NULL, 3 ), 
        ( 86, @domain_id, 'civicrm/admin/setting/url&reset=1',                  '{ts escape="sql"}Resource URLs{/ts}', 'Resource URLs', NULL, '', '82', '1', NULL, 4 ), 
        ( 87, @domain_id, 'civicrm/admin/setting/smtp&reset=1',                 '{ts escape="sql"}Outbound Email (SMTP/Sendmail){/ts}', 'Outbound Email', NULL, '', '82', '1', NULL, 5 ), 
        ( 88, @domain_id, 'civicrm/admin/setting/mapping&reset=1',              '{ts escape="sql"}Mapping and Geocoding{/ts}', 'Mapping and Geocoding', NULL, '', '82', '1', NULL, 6 ), 
        ( 89, @domain_id, 'civicrm/admin/paymentProcessor&reset=1',             '{ts escape="sql"}Payment Processors{/ts}', 'Payment Processors', NULL, '', '82', '1', NULL, 7 ), 
        ( 90, @domain_id, 'civicrm/admin/setting/localization&reset=1',         '{ts escape="sql"}Localization{/ts}', 'Localization', NULL, '', '82', '1', NULL, 8 ), 
        ( 91, @domain_id, 'civicrm/admin/setting/preferences/address&reset=1',  '{ts escape="sql"}Address Settings{/ts}', 'Address Settings', NULL, '', '82', '1', NULL, 9 ), 
        ( 92, @domain_id, 'civicrm/admin/setting/date&reset=1',                 '{ts escape="sql"}Date Formats{/ts}', 'Date Formats', NULL, '', '82', '1', NULL, 10 ), 
        ( 93, @domain_id, 'civicrm/admin/setting/uf&reset=1',                   '{ts escape="sql"}CMS Integration{/ts}', 'CMS Integration', NULL, '', '82', '1', NULL, 11 ), 
        ( 94, @domain_id, 'civicrm/admin/setting/misc&reset=1',                 '{ts escape="sql"}Miscellaneous (version check, search, reCAPTCHA...){/ts}', 'Miscellaneous', NULL, '', '82', '1', NULL, 12 ), 
        ( 95, @domain_id, 'civicrm/admin/options/safe_file_extension&group=safe_file_extension&reset=1', '{ts escape="sql"}Safe File Extensions{/ts}', 'Safe File Extensions', NULL, '', '82', '1', NULL, 13 ), 
        ( 96, @domain_id, 'civicrm/admin/setting/debug&reset=1',                '{ts escape="sql"}Debugging{/ts}', 'Debugging', NULL, '', '82', '1', NULL, 14 ), 
        
        ( 97, @domain_id, 'civicrm/admin/mapping&reset=1',                      '{ts escape="sql"}Import/Export Mappings{/ts}', 'Import/Export Mappings', NULL, '', '80', '1', NULL, 3 ), 
        ( 98, @domain_id, 'civicrm/admin/messageTemplates&reset=1',             '{ts escape="sql"}Message Templates{/ts}', 'Message Templates', NULL, '', '80', '1', NULL, 4 ), 
        ( 99, @domain_id, 'civicrm/admin/domain&action=update&reset=1',         '{ts escape="sql"}Domain Information{/ts}', 'Domain Information', NULL, '', '80', '1', NULL, 5 ), 
        ( 100,@domain_id, 'civicrm/admin/options/from_email_address&group=from_email_address&reset=1', '{ts escape="sql"}FROM Email Addresses{/ts}', 'FROM Email Addresses', NULL, '', '80', '1', NULL, 6 ), 
        ( 101,@domain_id, 'civicrm/admin/setting/updateConfigBackend&reset=1',  '{ts escape="sql"}Update Directory Path and URL{/ts}', 'Update Directory Path and URL', NULL, '', '80', '1', NULL, 7 ), 
        
        ( 102, @domain_id, NULL,                                                '{ts escape="sql"}Manage{/ts}', 'Manage', NULL, '', '73', '1', NULL, 4 ), 
        ( 103, @domain_id, 'civicrm/admin/deduperules&reset=1',                 '{ts escape="sql"}Find and Merge Duplicate Contacts{/ts}', 'Find and Merge Duplicate Contacts', '', '', '102', '1', NULL, 1 ), 
        ( 104, @domain_id, 'civicrm/admin/access&reset=1',                      '{ts escape="sql"}Access Control{/ts}', 'Access Control', NULL, '', '102', '1', NULL, 2 ), 
        ( 105, @domain_id, 'civicrm/admin/synchUser&reset=1',                   '{ts escape="sql"}Synchronize Users to Contacts{/ts}', 'Synchronize Users to Contacts', NULL, '', '102', '1', NULL, 3 ), 
        
        ( 106, @domain_id, NULL,                                                '{ts escape="sql"}Option Lists{/ts}', 'Option Lists', NULL, '', '73', '1', NULL, 5 ), 
        ( 107, @domain_id, 'civicrm/admin/options/activity_type&reset=1&group=activity_type', '{ts escape="sql"}Activity Types{/ts}', 'Activity Types', NULL, '', '106', '1', NULL, 1 ), 
        ( 108, @domain_id, 'civicrm/admin/reltype&reset=1',                     '{ts escape="sql"}Relationship Types{/ts}', 'Relationship Types', NULL, '', '106', '1', NULL, 2 ), 
        ( 109, @domain_id, 'civicrm/admin/tag&reset=1',                         '{ts escape="sql"}Tags (Categories){/ts}', 'Tags (Categories)', NULL, '', '106', '1', 1, 3 ), 
        ( 110, @domain_id, 'civicrm/admin/options/gender&reset=1&group=gender', '{ts escape="sql"}Gender Options{/ts}', 'Gender Options', NULL, '', '106', '1', NULL, 4 ), 
        ( 111, @domain_id, 'civicrm/admin/options/individual_prefix&group=individual_prefix&reset=1',   '{ts escape="sql"}Individual Prefixes (Ms, Mr...){/ts}', 'Individual Prefixes (Ms, Mr...)', NULL, '', '106', '1', NULL, 5 ), 
        ( 112, @domain_id, 'civicrm/admin/options/individual_suffix&group=individual_suffix&reset=1',   '{ts escape="sql"}Individual Suffixes (Jr, Sr...){/ts}', 'Individual Suffixes (Jr, Sr...)', NULL, '', '106', '1', 1, 6 ), 
        ( 113, @domain_id, 'civicrm/admin/options/addressee&group=addressee&reset=1',                   '{ts escape="sql"}Addressee Formats{/ts}', 'Addressee Formats', NULL, '', '106', '1', NULL, 7 ), 
        ( 114, @domain_id, 'civicrm/admin/options/email_greeting&group=email_greeting&reset=1',         '{ts escape="sql"}Email Greetings{/ts}', 'Email Greetings', NULL, '', '106', '1', NULL, 8 ), 
        ( 115, @domain_id, 'civicrm/admin/options/postal_greeting&group=postal_greeting&reset=1',       '{ts escape="sql"}Postal Greetings{/ts}', 'Postal Greetings', NULL, '', '106', '1', 1, 9 ), 
        ( 116, @domain_id, 'civicrm/admin/options/instant_messenger_service&group=instant_messenger_service&reset=1', '{ts escape="sql"}Instant Messenger Services{/ts}', 'Instant Messenger Services', NULL, '', '106', '1', NULL, 10 ), 
        ( 117, @domain_id, 'civicrm/admin/locationType&reset=1',                '{ts escape="sql"}Location Types (Home, Work...){/ts}', 'Location Types (Home, Work...)', NULL, '', '106', '1', NULL, 11 ), 
        ( 118, @domain_id, 'civicrm/admin/options/mobile_provider&group=mobile_provider&reset=1',   '{ts escape="sql"}Mobile Phone Providers{/ts}', 'Mobile Phone Providers', NULL, '', '106', '1', NULL, 12 ), 
        ( 119, @domain_id, 'civicrm/admin/options/phone_type&group=phone_type&reset=1',             '{ts escape="sql"}Phone Types{/ts}', 'Phone Types', NULL, '', '106', '1', NULL, 13 ), 
        ( 120, @domain_id, 'civicrm/admin/options/preferred_communication_method&group=preferred_communication_method&reset=1', '{ts escape="sql"}Preferred Communication Methods{/ts}', 'Preferred Communication Methods', NULL, '', '106', '1', NULL, 14 ), 
        
        ( 121, @domain_id, NULL,                                                                '{ts escape="sql"}CiviCase{/ts}', 'CiviCase', 'access CiviCase,administer CiviCRM', 'AND', '73', '1', NULL, 6 ), 
        ( 122, @domain_id, 'civicrm/admin/options/case_type&group=case_type&reset=1',           '{ts escape="sql"}Case Types{/ts}', 'Case Types', 'access CiviCase,administer CiviCRM', '', '121', '1', NULL, 1 ), 
        ( 123, @domain_id, 'civicrm/admin/options/redaction_rule&group=redaction_rule&reset=1', '{ts escape="sql"}Redaction Rules{/ts}', 'Redaction Rules', 'access CiviCase,administer CiviCRM', '', '121', '1', NULL, 2 ), 
        
        ( 124, @domain_id, NULL,                                                '{ts escape="sql"}CiviContribute{/ts}', 'CiviContribute', 'access CiviContribute,administer CiviCRM', 'AND', '73', '1', NULL, 7 ), 
        ( 125, @domain_id, 'civicrm/admin/contribute&reset=1&action=add',       '{ts escape="sql"}New Contribution Page{/ts}', 'New Contribution Page', NULL, '', '124', '1', NULL, 6 ), 
        ( 126, @domain_id, 'civicrm/admin/contribute&reset=1',                  '{ts escape="sql"}Manage Contribution Pages{/ts}', 'Manage Contribution Pages', NULL, '', '124', '1', '1', 7 ), 
        ( 127, @domain_id, 'civicrm/admin/pcp&reset=1',                         '{ts escape="sql"}Personal Campaign Pages{/ts}', 'Personal Campaign Pages', NULL, '', '124', '1', NULL, 8 ), 
        ( 128, @domain_id, 'civicrm/admin/contribute/managePremiums&reset=1',   '{ts escape="sql"}Premiums (Thank-you Gifts){/ts}', 'Premiums', NULL, '', '124', '1', 1, 9	 ), 
        ( 129, @domain_id, 'civicrm/admin/contribute/contributionType&reset=1', '{ts escape="sql"}Contribution Types{/ts}', 'Contribution Types', NULL, '', '124', '1', NULL, 10	 ), 
        ( 130, @domain_id, 'civicrm/admin/options/payment_instrument&group=payment_instrument&reset=1', '{ts escape="sql"}Payment Instruments{/ts}', 'Payment Instruments', NULL, '', '124', '1', NULL, 11	 ), 
        ( 131, @domain_id, 'civicrm/admin/options/accept_creditcard&group=accept_creditcard&reset=1',   '{ts escape="sql"}Accepted Credit Cards{/ts}', 'Accepted Credit Cards', NULL, '', '124', '1', NULL, 12	 ), 
        
        ( 132, @domain_id, NULL,                                                '{ts escape="sql"}CiviEvent{/ts}', 'CiviEvent', 'access CiviEvent,administer CiviCRM', 'AND', '73', '1', NULL, 8 ), 
        ( 133, @domain_id, 'civicrm/event/add&reset=1&action=add',              '{ts escape="sql"}New Event{/ts}', 'New Event', NULL, '', '132', '1', NULL, 1 ), 
        ( 134, @domain_id, 'civicrm/event/manage&reset=1',                      '{ts escape="sql"}Manage Events{/ts}', 'Manage Events', NULL, '', '132', '1', 1, 2 ), 
        ( 135, @domain_id, 'civicrm/admin/eventTemplate&reset=1',               '{ts escape="sql"}Event Templates{/ts}', 'Event Templates', 'access CiviEvent,administer CiviCRM', '', '132', '1', 1, 3 ), 
        ( 136, @domain_id, 'civicrm/admin/price&reset=1&action=add',            '{ts escape="sql"}New Price Set{/ts}', 'New Price Set', NULL, '', '132', '1', NULL, 4 ), 
        ( 137, @domain_id, 'civicrm/event/price&reset=1',                       '{ts escape="sql"}Manage Price Sets{/ts}', 'Manage Price Sets', NULL, '', '132', '1', 1, 5 ),
        ( 138, @domain_id, 'civicrm/admin/options/participant_listing&group=participant_listing&reset=1', '{ts escape="sql"}Participant Listing Templates{/ts}', 'Participant Listing Templates', NULL, '', '132', '1', NULL, 6 ), 
        ( 139, @domain_id, 'civicrm/admin/options/event_type&group=event_type&reset=1', '{ts escape="sql"}Event Types{/ts}', 'Event Types', NULL, '', '132', '1', NULL, 7 ), 
        ( 140, @domain_id, 'civicrm/admin/participant_status&reset=1',          '{ts escape="sql"}Participant Statuses{/ts}', 'Participant Statuses', NULL, '', '132', '1', NULL, 8 ), 
        ( 141, @domain_id, 'civicrm/admin/options/participant_role&group=participant_role&reset=1', '{ts escape="sql"}Participant Roles{/ts}', 'Participant Roles', NULL, '', '132', '1', NULL, 9 ), 
        
        ( 142, @domain_id, NULL,                                                        '{ts escape="sql"}CiviGrant{/ts}', 'CiviGrant', 'access CiviGrant,administer CiviCRM', 'AND', '73', '1', NULL, 9 ), 
        ( 143, @domain_id, 'civicrm/admin/options/grant_type&group=grant_type&reset=1', '{ts escape="sql"}Grant Types{/ts}', 'Grant Types', 'access CiviGrant,administer CiviCRM', '', '142', '1', NULL, 1 ), 
        
        ( 144, @domain_id, NULL,                                                '{ts escape="sql"}CiviMail{/ts}', 'CiviMail', 'access CiviMail,administer CiviCRM', 'AND', '73', '1', NULL, 10 ), 
        ( 145, @domain_id, 'civicrm/admin/component&reset=1',                   '{ts escape="sql"}Headers, Footers, and Automated Messages{/ts}', 'Headers, Footers, and Automated Messages', NULL, '', '144', '1', NULL, 1 ), 
        ( 146, @domain_id, 'civicrm/admin/messageTemplates&reset=1',            '{ts escape="sql"}Message Templates{/ts}', 'Message Templates', NULL, '', '144', '1', NULL, 2 ), 
        ( 147, @domain_id, 'civicrm/admin/options/from_email&group=from_email_address&reset=1', '{ts escape="sql"}From Email Addresses{/ts}', 'From Email Addresses', NULL, '', '144', '1', NULL, 3 ), 
        ( 148, @domain_id, 'civicrm/admin/mailSettings&reset=1',                '{ts escape="sql"}Mail Accounts{/ts}', 'Mail Accounts', NULL, '', '144', '1', NULL, 4 ), 
        
        ( 149, @domain_id, NULL,                                                '{ts escape="sql"}CiviMember{/ts}',     'CiviMember', 'access CiviMember,administer CiviCRM', 'AND', '73', '1', NULL, 11 ), 
        ( 150, @domain_id, 'civicrm/admin/member/membershipType&reset=1',       '{ts escape="sql"}Membership Types{/ts}', 'Membership Types', 'access CiviMember,administer CiviCRM', '', '149', '1', NULL, 1 ), 
        ( 151, @domain_id, 'civicrm/admin/member/membershipStatus&reset=1',     '{ts escape="sql"}Membership Status Rules{/ts}', 'Membership Status Rules', 'access CiviMember,administer CiviCRM', '', '149', '1', NULL, 2 ), 
        
        ( 152, @domain_id, NULL,                                                    '{ts escape="sql"}CiviReport{/ts}', 'CiviReport', 'access CiviReport,administer CiviCRM', 'AND', '73', '1', NULL, 12 ), 
        ( 153, @domain_id, 'civicrm/report/list&reset=1',                           '{ts escape="sql"}Reports Listing{/ts}', 'Reports Listing', NULL, '', '152', '1', NULL, 1 ), 
        ( 154, @domain_id, 'civicrm/admin/report/template/list&reset=1',            '{ts escape="sql"}Create Reports from Templates{/ts}', 'Create Reports from Templates', NULL, '', '152', '1', NULL, 2 ), 
        ( 155, @domain_id, 'civicrm/admin/report/options/report_template&reset=1',  '{ts escape="sql"}Manage Templates{/ts}', 'Manage Templates', NULL, '', '152', '1', NULL, 3 ), 
        ( 156, @domain_id, 'civicrm/admin/report/register&reset=1',                  '{ts escape="sql"}Register Report{/ts}', 'Register Report', 'administer Reports',  '', '152', '1', NULL, 4 ),

        ( 157, @domain_id, NULL,                                '{ts escape="sql"}Help{/ts}',               'Help',             NULL, '',     NULL, '1', NULL, 10), 
        ( 158, @domain_id, 'http://documentation.civicrm.org',  '{ts escape="sql"}Documentation{/ts}',      'Documentation',    NULL, 'AND', '157', '1', NULL, 1 ), 
        ( 159, @domain_id, 'http://forum.civicrm.org',          '{ts escape="sql"}Community Forums{/ts}',   'Community Forums', NULL, 'AND', '157', '1', NULL, 2 ), 
        ( 160, @domain_id, 'http://civicrm.org/participate',    '{ts escape="sql"}Participate{/ts}',        'Participate',      NULL, 'AND', '157', '1', NULL, 3 ), 
        ( 161, @domain_id, 'http://civicrm.org/aboutcivicrm',   '{ts escape="sql"}About{/ts}',              'About',            NULL, 'AND', '157', '1', NULL, 4 ),

        ( 162, @domain_id, 'civicrm/admin/setting/search?reset=1',              '{ts escape="sql"}Search Settings{/ts}', 'Search Settings', NULL, '', '82', '1', NULL, 10 ),
        
        ( 163, @domain_id, NULL,                                                     '{ts escape="sql"}Reports{/ts}',                        'Reports',                       'access CiviReport',   '',  NULL, '1', NULL, 8 ), 
        ( 164, @domain_id, 'civicrm/report/list&reset=1',                            '{ts escape="sql"}Reports Listing{/ts}',                'Reports Listing',                 NULL,                '', '163', '1', NULL, 1 ),
        ( 165, @domain_id, 'civicrm/admin/report/template/list&reset=1',             '{ts escape="sql"}Create Reports from Templates{/ts}',  'Create Reports from Templates', 'administer Reports',  '', '163', '1', 1,    2 ), 
        
        ( 166, @domain_id, 'civicrm/report/instance/1&reset=1',     '{ts escape="sql"}Constituent Report (Summary){/ts}',       '{literal}Constituent Report (Summary){/literal}',     'administer CiviCRM',       '',  163,  '1', NULL, 3 ),
        ( 167, @domain_id, 'civicrm/report/instance/2&reset=1',     '{ts escape="sql"}Constituent Report (Detail){/ts}',        '{literal}Constituent Report (Detail){/literal}',      'administer CiviCRM',       '',  163,  '1', NULL, 4 ),
        ( 168, @domain_id, 'civicrm/report/instance/3&reset=1',     '{ts escape="sql"}Donor Report (Summary){/ts}',             '{literal}Donor Report (Summary){/literal}',           'access CiviContribute',    '',  163,  '1', NULL, 5 ),
        ( 169, @domain_id, 'civicrm/report/instance/4&reset=1',     '{ts escape="sql"}Donor Report (Detail){/ts}',              '{literal}Donor Report (Detail){/literal}',            'access CiviContribute',    '',  163,  '1', NULL, 6 ),
        ( 170, @domain_id, 'civicrm/report/instance/5&reset=1',     '{ts escape="sql"}Donation Summary Report (Repeat){/ts}',   '{literal}Donation Summary Report (Repeat){/literal}', 'access CiviContribute',    '',  163,  '1', NULL, 7 ),
        ( 171, @domain_id, 'civicrm/report/instance/6&reset=1',     '{ts escape="sql"}SYBUNT Report{/ts}',                      'SYBUNT Report',                                       'access CiviContribute',    '',  163,  '1', NULL, 8 ),
        ( 172, @domain_id, 'civicrm/report/instance/7&reset=1',     '{ts escape="sql"}LYBUNT Report{/ts}',                      'LYBUNT Report',                                       'access CiviContribute',    '',  163,  '1', NULL, 9 ),
        ( 173, @domain_id, 'civicrm/report/instance/8&reset=1',     '{ts escape="sql"}Soft Credit Report{/ts}',                 'Soft Credit Report',                                  'access CiviContribute',    '',  163,  '1', NULL, 10 ),
        ( 174, @domain_id, 'civicrm/report/instance/9&reset=1',     '{ts escape="sql"}Membership Report (Summary){/ts}',        '{literal}Membership Report (Summary){/literal}',      'access CiviMember',        '',  163,  '1', NULL, 11 ),
        ( 175, @domain_id, 'civicrm/report/instance/10&reset=1',    '{ts escape="sql"}Membership Report (Detail){/ts}',         '{literal}Membership Report (Detail){/literal}',       'access CiviMember',        '',  163,  '1', NULL, 12 ),
        ( 176, @domain_id, 'civicrm/report/instance/11&reset=1',    '{ts escape="sql"}Membership Report (Lapsed){/ts}',         '{literal}Membership Report (Lapsed){/literal}',       'access CiviMember',        '',  163,  '1', NULL, 13 ),
        ( 177, @domain_id, 'civicrm/report/instance/12&reset=1',    '{ts escape="sql"}Event Participant Report (List){/ts}',    '{literal}Event Participant Report (List){/literal}',  'access CiviEvent',         '',  163,  '1', NULL, 14 ),
        ( 178, @domain_id, 'civicrm/report/instance/13&reset=1',    '{ts escape="sql"}Event Income Report (Summary){/ts}',      '{literal}Event Income Report (Summary){/literal}',    'access CiviEvent',         '',  163,  '1', NULL, 15 ),
        ( 179, @domain_id, 'civicrm/report/instance/14&reset=1',    '{ts escape="sql"}Event Income Report (Detail){/ts}',       '{literal}Event Income Report (Detail){/literal}',     'access CiviEvent',         '',  163,  '1', NULL, 16 ),
        ( 180, @domain_id, 'civicrm/report/instance/15&reset=1',    '{ts escape="sql"}Attendee List{/ts}',                      'Attendee List',                                       'access CiviEvent',         '',  163,  '1', NULL, 17 ),
        ( 181, @domain_id, 'civicrm/report/instance/16&reset=1',    '{ts escape="sql"}Activity Report{/ts}',                    'activity',                                            'administer CiviCRM',       '',  163,  '1', NULL, 18 ),
        ( 182, @domain_id, 'civicrm/report/instance/17&reset=1',    '{ts escape="sql"}Relationship Report{/ts}',                'Relationship Report',                                 'administer CiviCRM',       '',  163,  '1', NULL, 19 ),    
        ( 183, @domain_id, 'civicrm/report/instance/18&reset=1',    '{ts escape="sql"}Donation Summary Report (Organization){/ts}', '{literal}Donation Summary Report (Organization){/literal}', 'access CiviContribute', '',  163,  '1', NULL, 20 ),
        ( 184, @domain_id, 'civicrm/report/instance/19&reset=1',    '{ts escape="sql"}Donation Summary Report (Household){/ts}',    '{literal}Donation Summary Report (Household){/literal}',    'access CiviContribute', '',  163,  '1', NULL, 21 ),
        ( 185, @domain_id, 'civicrm/report/instance/20&reset=1',    '{ts escape="sql"}Top Donors Report{/ts}',                      'Top Donors Report',                                         'access CiviContribute', '',  163,  '1', NULL, 22 ),    
        ( 186, @domain_id, 'civicrm/report/instance/21&reset=1',    '{ts escape="sql"}Pledge Summary Report{/ts}',              'Pledge Summary Report',            'access CiviPledge',       '',  163,  '1', NULL, 23 ),
        ( 187, @domain_id, 'civicrm/report/instance/22&reset=1',    '{ts escape="sql"}Pledged But not Paid Report{/ts}',        'Pledged But not Paid Report',      'access CiviPledge',       '',  163,  '1', NULL, 24 );


    -- End navigation
    
    -- Enabled the Report Templates
    SELECT @option_group_id_report         := max(id) from civicrm_option_group where name = 'report_template';

    UPDATE civicrm_option_value
        SET is_active = 1
    WHERE   option_group_id = @option_group_id_report AND
            value IN ('activity','contribute/organizationSummary','contribute/householdSummary','contribute/topDonor','pledge/summary','pledge/pbnp');

    -- Add New templates
    INSERT INTO civicrm_option_value
        (option_group_id,         {localize field='label'}label{/localize},   value,                  name,                                   weight, {localize field='description'}description{/localize}, is_active, component_id)
    VALUES
        (@option_group_id_report, {localize}'Relationship Report'{/localize}, 'contact/relationship', 'CRM_Report_Form_Contact_Relationship', 23,     {localize}'Relationship Report'{/localize},           1,         NULL);
    -- adde new reports
    INSERT INTO `civicrm_report_instance`
        (title, report_id, description, permission, form_values)
    VALUES
        ('{ts escape="sql"}Activity Report{/ts}',                        'activity',                        'Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required)', 'administer CiviCRM', '{literal}a:22:{s:6:"fields"#a:7:{s:14:"contact_source"#s:1:"1"#s:16:"contact_assignee"#s:1:"1"#s:14:"contact_target"#s:1:"1"#s:16:"activity_type_id"#s:1:"1"#s:7:"subject"#s:1:"1"#s:18:"activity_date_time"#s:1:"1"#s:9:"status_id"#s:1:"1"#}s:17:"contact_source_op"#s:3:"has"#s:20:"contact_source_value"#s:0:""#s:19:"contact_assignee_op"#s:3:"has"#s:22:"contact_assignee_value"#s:0:""#s:17:"contact_target_op"#s:3:"has"#s:20:"contact_target_value"#s:0:""#s:27:"activity_date_time_relative"#s:10:"this.month"#s:23:"activity_date_time_from"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:21:"activity_date_time_to"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:10:"subject_op"#s:3:"has"#s:13:"subject_value"#s:0:""#s:19:"activity_type_id_op"#s:2:"in"#s:22:"activity_type_id_value"#a:0:{}s:12:"status_id_op"#s:2:"in"#s:15:"status_id_value"#a:0:{}s:11:"description"#s:126:"Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required)"#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:18:"administer CiviCRM"#s:6:"groups"#s:0:""#}{/literal}'),
        ('{ts escape="sql"}Relationship Report{/ts}',                    'contact/relationship',            'Gives relationship details between two contacats', 'administer CiviCRM', '{literal}a:27:{s:6:"fields"#a:4:{s:14:"display_name_a"#s:1:"1"#s:14:"display_name_b"#s:1:"1"#s:9:"label_a_b"#s:1:"1"#s:7:"email_a"#s:1:"1"#}s:17:"contact_name_a_op"#s:3:"has"#s:20:"contact_name_a_value"#s:0:""#s:17:"contact_name_b_op"#s:3:"has"#s:20:"contact_name_b_value"#s:0:""#s:6:"id_min"#s:0:""#s:6:"id_max"#s:0:""#s:5:"id_op"#s:3:"lte"#s:8:"id_value"#s:0:""#s:17:"contact_type_a_op"#s:2:"in"#s:20:"contact_type_a_value"#a:0:{}s:17:"contact_type_b_op"#s:2:"in"#s:20:"contact_type_b_value"#a:0:{}s:8:"label_op"#s:2:"eq"#s:11:"label_value"#s:1:" "#s:13:"country_id_op"#s:2:"in"#s:16:"country_id_value"#a:0:{}s:20:"state_province_id_op"#s:2:"in"#s:23:"state_province_id_value"#a:0:{}s:6:"gid_op"#s:2:"in"#s:9:"gid_value"#a:0:{}s:11:"description"#s:48:"Gives relationship details between two contacts"#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:18:"administer CiviCRM"#s:6:"groups"#s:0:""#}{/literal}'),
        ('{ts escape="sql"}Donation Summary Report (Organization){/ts}', 'contribute/organizationSummary',  'Displays a detailed contribution report for Organization relationships with contributors, as to if contribution done was  from an employee of some organization or from that Organization itself.', 'access CiviContribute', '{literal}a:20:{s:6:"fields"#a:5:{s:17:"organization_name"#s:1:"1"#s:12:"display_name"#s:1:"1"#s:12:"total_amount"#s:1:"1"#s:22:"contribution_status_id"#s:1:"1"#s:12:"receive_date"#s:1:"1"#}s:20:"organization_name_op"#s:3:"has"#s:23:"organization_name_value"#s:0:""#s:23:"relationship_type_id_op"#s:2:"eq"#s:26:"relationship_type_id_value"#s:5:"4_b_a"#s:21:"receive_date_relative"#s:1:"0"#s:17:"receive_date_from"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:15:"receive_date_to"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:16:"total_amount_min"#s:0:""#s:16:"total_amount_max"#s:0:""#s:15:"total_amount_op"#s:3:"lte"#s:18:"total_amount_value"#s:0:""#s:25:"contribution_status_id_op"#s:2:"in"#s:28:"contribution_status_id_value"#a:1:{i:0#s:1:"1"#}s:11:"description"#s:193:"Displays a detailed contribution report for Organization relationships with contributors, as to if contribution done was  from an employee of some organization or from that Organization itself."#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:21:"access CiviContribute"#s:6:"groups"#s:0:""#}{/literal}'),
        ('{ts escape="sql"}Donation Summary Report (Household){/ts}',    'contribute/householdSummary',     'Provides a detailed report for Contributions made by contributors(Or Household itself) who are having a relationship with household (For ex a Contributor is Head of Household for some household or is a member of.)', 'access CiviContribute', '{literal}a:20:{s:6:"fields"#a:5:{s:14:"household_name"#s:1:"1"#s:12:"display_name"#s:1:"1"#s:12:"total_amount"#s:1:"1"#s:22:"contribution_status_id"#s:1:"1"#s:12:"receive_date"#s:1:"1"#}s:17:"household_name_op"#s:3:"has"#s:20:"household_name_value"#s:0:""#s:23:"relationship_type_id_op"#s:2:"eq"#s:26:"relationship_type_id_value"#s:5:"6_b_a"#s:21:"receive_date_relative"#s:1:"0"#s:17:"receive_date_from"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:15:"receive_date_to"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:16:"total_amount_min"#s:0:""#s:16:"total_amount_max"#s:0:""#s:15:"total_amount_op"#s:3:"lte"#s:18:"total_amount_value"#s:0:""#s:25:"contribution_status_id_op"#s:2:"in"#s:28:"contribution_status_id_value"#a:1:{i:0#s:1:"1"#}s:11:"description"#s:213:"Provides a detailed report for Contributions made by contributors(Or Household itself) who are having a relationship with household (For ex a Contributor is Head of Household for some household or is a member of.)"#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:21:"access CiviContribute"#s:6:"groups"#s:0:""#}{/literal}'),
        ('{ts escape="sql"}Top Donors Report{/ts}',                      'contribute/topDonor',             'Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).', 'access CiviContribute', '{literal}a:20:{s:6:"fields"#a:2:{s:12:"display_name"#s:1:"1"#s:12:"total_amount"#s:1:"1"#}s:21:"receive_date_relative"#s:9:"this.year"#s:17:"receive_date_from"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:15:"receive_date_to"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:15:"total_range_min"#s:0:""#s:15:"total_range_max"#s:0:""#s:14:"total_range_op"#s:2:"eq"#s:17:"total_range_value"#s:0:""#s:23:"contribution_type_id_op"#s:2:"in"#s:26:"contribution_type_id_value"#a:0:{}s:25:"contribution_status_id_op"#s:2:"in"#s:28:"contribution_status_id_value"#a:1:{i:0#s:1:"1"#}s:6:"gid_op"#s:2:"in"#s:9:"gid_value"#a:0:{}s:11:"description"#s:148:"Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors)."#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:21:"access CiviContribute"#s:6:"groups"#s:0:""#}{/literal}'),
        ('{ts escape="sql"}Pledge Summary Report{/ts}',                  'pledge/summary',                  'Updates you with your Pledge Summary (if any) such as your pledge status, next payment date, amount, payment due, total amount paid etc.', 'access CiviPledge', '{literal}a:24:{s:6:"fields"#a:4:{s:12:"display_name"#s:1:"1"#s:10:"country_id"#s:1:"1"#s:6:"amount"#s:1:"1"#s:9:"status_id"#s:1:"1"#}s:12:"sort_name_op"#s:3:"has"#s:15:"sort_name_value"#s:0:""#s:6:"id_min"#s:0:""#s:6:"id_max"#s:0:""#s:5:"id_op"#s:3:"lte"#s:8:"id_value"#s:0:""#s:27:"pledge_create_date_relative"#s:1:"0"#s:23:"pledge_create_date_from"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:21:"pledge_create_date_to"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:17:"pledge_amount_min"#s:0:""#s:17:"pledge_amount_max"#s:0:""#s:16:"pledge_amount_op"#s:3:"lte"#s:19:"pledge_amount_value"#s:0:""#s:6:"sid_op"#s:2:"in"#s:9:"sid_value"#a:0:{}s:6:"gid_op"#s:2:"in"#s:9:"gid_value"#a:0:{}s:11:"description"#s:136:"Updates you with your Pledge Summary (if any) such as your pledge status, next payment date, amount, payment due, total amount paid etc."#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:17:"access CiviPledge"#s:6:"groups"#s:0:""#}{/literal}'),
        ('{ts escape="sql"}Pledged But not Paid Report{/ts}',            'pledge/pbnp',                     'Pledged but not Paid Report', 'access CiviPledge', '{literal}a:14:{s:6:"fields"#a:6:{s:12:"display_name"#s:1:"1"#s:18:"pledge_create_date"#s:1:"1"#s:6:"amount"#s:1:"1"#s:14:"scheduled_date"#s:1:"1"#s:10:"country_id"#s:1:"1"#s:5:"email"#s:1:"1"#}s:27:"pledge_create_date_relative"#s:1:"0"#s:23:"pledge_create_date_from"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:21:"pledge_create_date_to"#a:3:{s:1:"M"#s:0:""#s:1:"d"#s:0:""#s:1:"Y"#s:0:""#}s:23:"contribution_type_id_op"#s:2:"in"#s:26:"contribution_type_id_value"#a:0:{}s:6:"gid_op"#s:2:"in"#s:9:"gid_value"#a:0:{}s:11:"description"#s:27:"Pledged but not Paid Report"#s:13:"email_subject"#s:0:""#s:8:"email_to"#s:0:""#s:8:"email_cc"#s:0:""#s:10:"permission"#s:17:"access CiviPledge"#s:6:"groups"#s:0:""#}{/literal}');


    -- CRM-4114
    ALTER TABLE `civicrm_report_instance`
        ADD navigation_id int unsigned  COMMENT 'FK to navigation ID',
        ADD CONSTRAINT `FK_civicrm_report_instance_navigation_id` FOREIGN KEY (`navigation_id`) REFERENCES `civicrm_navigation` (`id`) ON DELETE SET NULL;

    UPDATE civicrm_report_instance
	SET navigation_id = CASE id
                                WHEN 1  THEN 166
                                WHEN 2  THEN 167
                                WHEN 3  THEN 168
                                WHEN 4  THEN 169
                                WHEN 5  THEN 170
                                WHEN 6  THEN 171
                                WHEN 7  THEN 172
                                WHEN 8  THEN 173
                                WHEN 9  THEN 174
                                WHEN 10 THEN 175
                                WHEN 11 THEN 176
                                WHEN 12 THEN 177
                                WHEN 13 THEN 178
                                WHEN 14 THEN 179
                                WHEN 15 THEN 180
                                WHEN 16 THEN 181
                                WHEN 17 THEN 182
                                WHEN 18 THEN 183
                                WHEN 19 THEN 184
                                WHEN 20 THEN 185
                                WHEN 21 THEN 186
                                WHEN 22 THEN 187
                                ELSE navigation_id
                            END;
                            
    -- CRM-4414
    -- Add individual, organization and household default profile
    INSERT INTO civicrm_uf_group
        (name,               group_type,             {localize field='title'}title{/localize}, is_reserved)
    VALUES
        ('new_individual',   'Individual,Contact',   {localize}'New Individual'{/localize},    1),
        ('new_organization', 'Organization,Contact', {localize}'New Organization'{/localize},  1),
        ('new_household',    'Household,Contact',    {localize}'New Household'{/localize},     1);

    SELECT @uf_group_id_individual   := max(id) from civicrm_uf_group where name = 'new_individual';
    SELECT @uf_group_id_organization := max(id) from civicrm_uf_group where name = 'new_organization';
    SELECT @uf_group_id_household    := max(id) from civicrm_uf_group where name = 'new_household';

    INSERT INTO `civicrm_uf_join`
        ( `is_active`, `module`, `entity_table`, `entity_id`, `weight`, `uf_group_id` )
    VALUES
        ( 1,           'Profile', NULL,           NULL,        3,       @uf_group_id_individual   ),
        ( 1,           'Profile', NULL,           NULL,        4,       @uf_group_id_organization ),
        ( 1,           'Profile', NULL,           NULL,        5,       @uf_group_id_household    );
    
    INSERT INTO civicrm_uf_field
        (uf_group_id,               field_name,          is_required, is_reserved, weight, {localize field='label'}label{/localize}, field_type)
    VALUES
        (@uf_group_id_individual,   'first_name',        1,           0,           1,      {localize}'First Name'{/localize},        'Individual'  ),
        (@uf_group_id_individual,   'last_name',         1,           0,           2,      {localize}'Last Name'{/localize},         'Individual'  ),
        (@uf_group_id_individual,   'email',             1,           0,           3,      {localize}'Email Address'{/localize},     'Contact'     ),
        (@uf_group_id_organization, 'organization_name', 1,           0,           1,      {localize}'Organization Name'{/localize}, 'Organization'),
        (@uf_group_id_organization, 'email',             1,           0,           2,      {localize}'Email Address'{/localize},     'Contact'     ),
        (@uf_group_id_household,    'household_name',    1,           0,           1,      {localize}'Household Name'{/localize},    'Household'   ),
        (@uf_group_id_household,    'email',             1,           0,           2,      {localize}'Email Address'{/localize},     'Contact'     );
    
    -- State / province
    -- CRM-4534 CRM-4686 CRM-4769
        INSERT INTO civicrm_state_province
            (id, name, abbreviation, country_id)
        VALUES
            (5218,  'Distrito Federal', 'DIF', 1140),
            (10004, 'Bonaire',          'BON', 1151),
            (10005, 'Curaçao',         'CUR', 1151),
            (10006, 'Saba',             'SAB', 1151),
            (10007, 'St. Eustatius',    'EUA', 1151),
            (10008, 'St. Maarten',      'SXM', 1151),
	    (10009, "Jura", 		"39" , 1076);

    -- CRM-4587 CRM-4534 CRM-4762 CRM-4394
    -- Update the name
       UPDATE civicrm_state_province
            SET name = CASE id
                WHEN 1859 THEN "Sofia"
                WHEN 3707 THEN "Ulaanbaatar"
                WHEN 2879 THEN "Achaïa"
                WHEN 3808 THEN "Coahuila"
                WHEN 3809 THEN "Colima"
                WHEN 3811 THEN "Chihuahua"
	    ELSE name	
	    END,
            abbreviation = CASE id
		WHEN 2543 THEN "36"
	        WHEN 2544 THEN "35"
	    ELSE abbreviation	
	    END,
	    country_id = CASE id
	    	WHEN 1637 THEN 1008 
	    ELSE country_id

        END;

    -- CRM-4633
    ALTER TABLE `civicrm_contact`
        ADD `do_not_sms` tinyint(4) default '0' AFTER `do_not_mail`;

    -- CRM-4664
    ALTER TABLE `civicrm_option_value`
        MODIFY `name` VARCHAR(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Stores a fixed (non-translated) name for this option value. Lookup functions should use the name as the key for the option value row.';

    -- CRM-4687
    -- set activity_date = due_date and drop due_date_time column from civicrm_activity.
    UPDATE civicrm_activity ca INNER JOIN civicrm_case_activity cca ON ca.id = cca.activity_id 
        SET activity_date_time = COALESCE( ca.activity_date_time, ca.due_date_time );

    ALTER TABLE civicrm_activity DROP COLUMN due_date_time;

    -- CRM-4120, CRM-4319, CRM-4326, CRM-4464
    ALTER TABLE civicrm_event
        CHANGE default_discount_id default_discount_fee_id INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'FK to civicrm_option_value.',
        ADD COLUMN is_template tinyint(4) default NULL COMMENT 'whether the event has template',
        {localize field='template_title'}ADD COLUMN template_title varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Event Template Title'{/localize},
        ADD COLUMN has_waitlist tinyint(4) default NULL COMMENT 'Whether the event has waitlist support',
        ADD COLUMN requires_approval tinyint(4) default NULL COMMENT 'Whether participants require approval before they can finish registering.',
        ADD COLUMN expiration_time int unsigned   DEFAULT NULL COMMENT 'Expire pending but unconfirmed registrations after this many hours.',
        {localize field='waitlist_text'}ADD COLUMN waitlist_text text collate utf8_unicode_ci default NULL COMMENT 'Text to display when the event is full, but participants can signup for a waitlist.'{/localize},
        {localize field='approval_req_text'}ADD COLUMN approval_req_text text collate utf8_unicode_ci default NULL COMMENT 'Text to display when the approval is required to complete registration for an event.'{/localize};

    INSERT INTO civicrm_event 
        (is_template, {localize field='template_title'}template_title{/localize}, event_type_id, default_role_id, participant_listing_id, is_public, is_monetary, is_online_registration, is_multiple_registrations, allow_same_participant_emails, is_email_confirm, contribution_type_id, {localize field='fee_label'}fee_label{/localize}, {localize field='confirm_title'}confirm_title{/localize}, {localize field='thankyou_title'}thankyou_title{/localize}, {localize field='confirm_from_name'}confirm_from_name{/localize}, confirm_from_email, is_active)
    VALUES
        (1, {localize}'Free Meeting without Online Registration'{/localize}, 4, 1, 1, 1, 0, 0, null, null, null,  null, {localize}null{/localize},             {localize}null{/localize},                                    {localize}null{/localize},                      {localize}null{/localize},                   null,                          1), 
        (1, {localize}'Free Meeting with Online Registration'{/localize},    4, 1, 1, 1, 0, 1, 1,    1,    0,     null, {localize}null{/localize},             {localize}'Confirm Your Registration Information'{/localize}, {localize}'Thanks for Registering!'{/localize}, {localize}null{/localize},                   null,                          1),
        (1, {localize}'Paid Conference with Online Registration'{/localize}, 1, 1, 1, 1, 1, 1, 1,    1,    1,     4,    {localize}'Conference Fee'{/localize}, {localize}'Confirm Your Registration Information'{/localize}, {localize}'Thanks for Registering!'{/localize}, {localize}'Event Template Dept.'{/localize}, 'event_templates@example.org', 1);
    
    -- CRM-4138
    ALTER TABLE `civicrm_payment_processor_type`
        ADD COLUMN `payment_type` int unsigned   DEFAULT 1 COMMENT 'Payment Type: Credit or Debit';
    ALTER TABLE `civicrm_payment_processor`
        ADD COLUMN `payment_type` int unsigned   DEFAULT 1 COMMENT 'Payment Type: Credit or Debit';

    -- CRM-4605
    -- A. upgrade wt and val by 2
    -- B. Insert Custom data and Address as group for first two empty location 
    -- C. Update Communication Pref name  
    -- D. Swap wt and value for Comm Pref, Notes, Demographics and make sure these record has to have wt and val 3, 4, 5 in sequence.

    -- get option group id for contact_edit_options
    SELECT @option_group_id_ceOpt := max(id) from civicrm_option_group where name = 'contact_edit_options';

    -- increment all wt and val by 2 and make first two location empty.
    UPDATE civicrm_option_value
        SET value = value + 2, weight = weight + 2
    WHERE option_group_id = @option_group_id_ceOpt;

    -- insert value for Custom Data and Address at first two locations.
    INSERT INTO civicrm_option_value
        (option_group_id,        {localize field='label'}label{/localize}, value, name,         grouping, filter, is_default, weight, is_optgroup, is_reserved, is_active)
    VALUES
        (@option_group_id_ceOpt, {localize}'Custom Data'{/localize},       1,     'CustomData', NULL,     0,      NULL,       1,      0,           0,           1),
        (@option_group_id_ceOpt, {localize}'Address'{/localize},           2,     'Address',    NULL,     0,      NULL,       2,      0,           0,           1);
    
    -- update Comm pref group name.
    UPDATE civicrm_option_value
        SET name = 'CommunicationPreferences'
    WHERE option_group_id=@option_group_id_ceOpt AND name = 'CommBlock';

    -- 1. Communication pref.
    -- swap wt and val and make commumication pref wt and val = 3
    Update civicrm_option_value otherRecord, civicrm_option_value commPref
        SET otherRecord.value = commPref.value, otherRecord.weight = commPref.weight, commPref.value = 3,  commPref.weight = 3
    WHERE  otherRecord.value = 3 AND commPref.name = 'CommunicationPreferences' AND commPref.option_group_id = @option_group_id_ceOpt AND otherRecord.option_group_id = @option_group_id_ceOpt;

    -- make sure comm has val and wt = 3 
    Update civicrm_option_value
        SET value = 3, weight = 3
    WHERE name = 'CommunicationPreferences' and option_group_id = @option_group_id_ceOpt;

    -- 2.  Notes.
    -- swap wt and val and make notes wt and val = 4
    Update civicrm_option_value otherRecord, civicrm_option_value notes
        SET otherRecord.value = notes.value, otherRecord.weight = notes.weight, notes.value = 4,  notes.weight=4
    WHERE  otherRecord.value = 4 AND notes.name = 'Notes' AND notes.option_group_id = @option_group_id_ceOpt AND otherRecord.option_group_id = @option_group_id_ceOpt;

    -- make sure Notes has val and wt = 4
    Update civicrm_option_value
        SET value = 4, weight = 4
    WHERE name = 'Notes' and option_group_id = @option_group_id_ceOpt;

    -- 3.  Demographics.
    -- swap wt and val and make demographics wt and val = 5
    Update civicrm_option_value otherRecord, civicrm_option_value demographics
        SET otherRecord.value = demographics.value, otherRecord.weight = demographics.weight, demographics.value = 5,  demographics.weight=5
    WHERE  otherRecord.value = 5 AND demographics.name = 'Demographics' AND demographics.option_group_id = @option_group_id_ceOpt AND otherRecord.option_group_id = @option_group_id_ceOpt;

    -- make sure Demoghraphics has val and wt = 5 
    Update civicrm_option_value
        SET value = 5, weight = 5
    WHERE name = 'Demographics' and option_group_id = @option_group_id_ceOpt;

    -- move location blocks to contact_edit_options.
    SELECT @max_wt  := max(weight) from civicrm_option_value where option_group_id=@option_group_id_ceOpt;
    SELECT @max_val := max(weight) from civicrm_option_value where option_group_id=@option_group_id_ceOpt;
    INSERT INTO civicrm_option_value
        (option_group_id,        {localize field='label'}label{/localize}, value,                           name,     grouping, filter, is_default, weight,                        is_optgroup, is_reserved, is_active, component_id, visibility_id)
    VALUES
        (@option_group_id_ceOpt, {localize}'Email'{/localize},             (SELECT @max_val := @max_val+1), 'Email',  NULL,     1,      NULL,       (SELECT @max_wt := @max_wt+1), 0,           0,           1,         NULL,         NULL),
        (@option_group_id_ceOpt, {localize}'Phone'{/localize},             (SELECT @max_val := @max_val+1), 'Phone',  NULL,     1,      NULL,       (SELECT @max_wt := @max_wt+1), 0,           0,           1,         NULL,         NULL),
        (@option_group_id_ceOpt, {localize}'Instant Messenger'{/localize}, (SELECT @max_val := @max_val+1), 'IM',     NULL,     1,      NULL,       (SELECT @max_wt := @max_wt+1), 0,           0,           1,         NULL,         NULL),
        (@option_group_id_ceOpt, {localize}'Open ID'{/localize},           (SELECT @max_val := @max_val+1), 'OpenID', NULL,     1,      NULL,       (SELECT @max_wt := @max_wt+1), 0,           0,           1,         NULL,         NULL);
    
    -- remove location blocks from address_options.
    SELECT @option_group_id_adOpt := max(id) from civicrm_option_group where name = 'address_options';
    DELETE FROM civicrm_option_value where option_group_id = @option_group_id_adOpt AND name IN ( 'im', 'openid' );

    -- update civicrm_preferences.contact_edit_options.
    -- ideally we should append value, but we did changed wt and values so lets reset it to default.
    UPDATE  civicrm_preferences 
        SET  contact_edit_options = (   SELECT  CONCAT( GROUP_CONCAT('',value SEPARATOR ''), '' ) 
                                        FROM  civicrm_option_value 
                                        WHERE  option_group_id = @option_group_id_ceOpt 
                                        Group by  option_group_id
                                    )
    WHERE  is_domain = 1 AND  contact_id IS NULL;
    -- End of CRM-4605


    -- CRM-4575
    -- drop column greeting type and custom greeting from civicrm_contact
    ALTER TABLE `civicrm_contact`
        DROP `greeting_type_id`,
        DROP `custom_greeting`;

    -- delete greeting type option group
    SELECT @og_id_greeting := max(id) from civicrm_option_group where name = 'greeting_type';
    DELETE FROM civicrm_option_value WHERE option_group_id = @og_id_greeting;
    DELETE FROM civicrm_option_group WHERE              id = @og_id_greeting;

    -- add email greeting, postal greeting and addressee fields
    ALTER TABLE `civicrm_contact` 
        ADD `email_greeting_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'FK to civicrm_option_value.id, that has to be valid registered Email Greeting.' AFTER `suffix_id`,
        ADD `postal_greeting_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'FK to civicrm_option_value.id, that has to be valid registered Postal Greeting.' AFTER `email_greeting_id`, 
        ADD `addressee_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'FK to civicrm_option_value.id, that has to be valid registered Addressee.' AFTER `postal_greeting_id`,        
        {localize field='email_greeting_display'}ADD email_greeting_display VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Cache Email greeting.'  AFTER email_greeting_id{/localize},
        {localize field='postal_greeting_display'}ADD postal_greeting_display VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Cache Postal  greeting.' AFTER postal_greeting_id{/localize},
        {localize field='addressee_display'}ADD addressee_display VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Cache Addressee.'  AFTER addressee_id{/localize},
        ADD `email_greeting_custom`  VARCHAR(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Custom Email Greeting.' AFTER `email_greeting_id`,
        ADD `postal_greeting_custom`  VARCHAR(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Custom Postal greeting.' AFTER `postal_greeting_id`,
        ADD `addressee_custom`  VARCHAR(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Custom Addressee.' AFTER `addressee_id`;
        

    SELECT @og_id_emailGreeting   := max(id) FROM civicrm_option_group WHERE name = 'email_greeting';
    SELECT @og_id_postalGreeting  := max(id) FROM civicrm_option_group WHERE name = 'postal_greeting';
    SELECT @og_id_addressee       := max(id) FROM civicrm_option_group WHERE name = 'addressee';

    -- add option values for email greeting, postal greeting and addressee
    INSERT INTO civicrm_option_value
      (option_group_id,       value, filter, is_default, weight, is_reserved, is_active, {localize field='label'}label{/localize}, name) VALUES

      -- email greetings.
      (@og_id_emailGreeting,  1, 1, 1, 1, 0, 1, {localize}'Dear {ldelim}contact.first_name{rdelim}'{/localize}, 'Dear {ldelim}contact.first_name{rdelim}'),
      (@og_id_emailGreeting,  2, 1, 0, 2, 0, 1, {localize}'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.first_name{rdelim} {ldelim}contact.last_name{rdelim}'{/localize}, 'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.first_name{rdelim} {ldelim}contact.last_name{rdelim}'),
      (@og_id_emailGreeting,  3, 1, 0, 3, 0, 1, {localize}'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.last_name{rdelim}'{/localize}, 'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.last_name{rdelim}'),
      (@og_id_emailGreeting,  4, 0, 0, 4, 1, 1, {localize}'Customized'{/localize}, 'Customized'),
      (@og_id_emailGreeting,  5, 2, 1, 5, 0, 1, {localize}'Dear {ldelim}contact.household_name{rdelim}'{/localize}, 'Dear {ldelim}contact.househols_name{rdelim}'),

      -- postal greeting.
      (@og_id_postalGreeting, 1, 1, 1, 1, 0, 1, {localize}'Dear {ldelim}contact.first_name{rdelim}'{/localize}, 'Dear {ldelim}contact.first_name{rdelim}'),
      (@og_id_postalGreeting, 2, 1, 0, 2, 0, 1, {localize}'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.first_name{rdelim} {ldelim}contact.last_name{rdelim}'{/localize}, 'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.first_name{rdelim} {ldelim}contact.last_name{rdelim}'),
      (@og_id_postalGreeting, 3, 1, 0, 3, 0, 1, {localize}'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.last_name{rdelim}'{/localize}, 'Dear {ldelim}contact.individual_prefix{rdelim} {ldelim}contact.last_name{rdelim}'),
      (@og_id_postalGreeting, 4, 0, 0, 4, 1, 1, {localize}'Customized'{/localize}, 'Customized'),
      (@og_id_postalGreeting, 5, 2, 1, 5, 0, 1, {localize}'Dear {ldelim}contact.household_name{rdelim}'{/localize}, 'Dear {ldelim}contact.househols_name{rdelim}'),

      -- addressee.
      (@og_id_addressee,      1, 1, 1, 1, 0, 1, {localize}'{ldelim}contact.individual_prefix{rdelim}{ldelim} {rdelim} {ldelim}contact.first_name{rdelim}{ldelim} {rdelim}{ldelim}contact.middle_name{rdelim}{ldelim} {rdelim}{ldelim}contact.last_name{rdelim}{ldelim} {rdelim}{ldelim}contact.individual_suffix{rdelim}'{/localize}, '{ldelim}contact.individual_prefix{rdelim}{ldelim} {rdelim} {ldelim}contact.first_name{rdelim}{ldelim} {rdelim}{ldelim}contact.middle_name{rdelim}{ldelim} {rdelim}{ldelim}contact.last_name{rdelim}{ldelim} {rdelim}{ldelim}contact.individual_suffix{rdelim}'),
      (@og_id_addressee,      2, 2, 1, 2, 0, 1, {localize}'{ldelim}contact.household_name{rdelim}'{/localize}, '{ldelim}contact.household_name{rdelim}'),
      (@og_id_addressee,      3, 3, 1, 3, 0, 1, {localize}'{ldelim}contact.organization_name{rdelim}'{/localize}, '{ldelim}contact.organization_name{rdelim}'),
      (@og_id_addressee,      4, 0, 0, 4, 1, 1, {localize}'Customized'{/localize}, 'Customized');
                            
    -- Set civicrm_contact.email_greeting_id and postal_greeting_id to default value for the given contact type. 
    SELECT @value := value
    FROM   civicrm_option_value 
    WHERE  civicrm_option_value.option_group_id = @og_id_emailGreeting
            AND civicrm_option_value.filter     = 1 
            AND civicrm_option_value.is_default = 1;
    UPDATE civicrm_contact
        SET 
            {localize field='email_greeting_display,  first_name'}email_greeting_display  = CONCAT("Dear ", first_name){/localize},
            {localize field='postal_greeting_display, first_name'}postal_greeting_display = CONCAT("Dear ", first_name){/localize},
            email_greeting_id       = @value,
            postal_greeting_id      = @value                        
    WHERE contact_type = 'Individual';

    SELECT @value := value
    FROM civicrm_option_value
    WHERE civicrm_option_value.option_group_id  = @og_id_emailGreeting
            AND civicrm_option_value.filter     = 2 
            AND civicrm_option_value.is_default = 1;
    UPDATE civicrm_contact
        SET             
            {localize field='email_greeting_display,  household_name'}email_greeting_display  = CONCAT("Dear ", household_name){/localize},
            {localize field='postal_greeting_display, household_name'}postal_greeting_display = CONCAT("Dear ", household_name){/localize},
            email_greeting_id       = @value,
            postal_greeting_id      = @value
    WHERE contact_type = 'Household';
    -- Set civicrm_contact.addressee_id to default value for the given contact type. 
    SELECT @value := value
    FROM civicrm_option_value
    WHERE civicrm_option_value.option_group_id  = @og_id_addressee
            AND civicrm_option_value.filter     = 1
            AND civicrm_option_value.is_default = 1;
    UPDATE civicrm_contact
        SET
          {localize field='addressee_display, display_name'}addressee_display = display_name{/localize},
          addressee_id   = @value
    WHERE contact_type   = 'Individual';

    SELECT @value := value
    FROM civicrm_option_value
    WHERE civicrm_option_value.option_group_id  = @og_id_addressee
            AND civicrm_option_value.filter     = 2
            AND civicrm_option_value.is_default = 1;
    UPDATE civicrm_contact
        SET
            {localize field='addressee_display, household_name'}addressee_display = household_name{/localize},
            addressee_id      = @value
    WHERE  contact_type      = 'Household';

    SELECT @value := value
    FROM civicrm_option_value 
        WHERE civicrm_option_value.option_group_id = @og_id_addressee
            AND civicrm_option_value.filter        = 3 
            AND civicrm_option_value.is_default    = 1;
    UPDATE civicrm_contact
        SET 
            {localize field='addressee_display, organization_name'}addressee_display = organization_name{/localize},
           addressee_id      = @value
    WHERE  contact_type      = 'Organization';

    -- CRM-4610
    ALTER TABLE `civicrm_group_organization`
        DROP FOREIGN KEY `FK_civicrm_group_organization_group_id`,
        DROP FOREIGN KEY `FK_civicrm_group_organization_organization_id`;
    
    ALTER TABLE `civicrm_group_organization`
        ADD CONSTRAINT `FK_civicrm_group_organization_group_id` FOREIGN KEY (`group_id`) REFERENCES `civicrm_group` (`id`) ON DELETE CASCADE,
        ADD CONSTRAINT `FK_civicrm_group_organization_organization_id` FOREIGN KEY (`organization_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
        ADD UNIQUE `UI_group_organization` ( `group_id` , `organization_id` );
    
    --  CRM-4697
    
    ALTER TABLE `civicrm_payment_processor`
        ADD `domain_id` INT(10) UNSIGNED NOT NULL COMMENT 'Which Domain is this match entry for' AFTER `id`;
    UPDATE `civicrm_payment_processor` SET domain_id = @domain_id;
    ALTER TABLE `civicrm_payment_processor`    
        ADD CONSTRAINT `FK_civicrm_payment_processor_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);
    
    ALTER TABLE `civicrm_membership_type`
        ADD `domain_id` INT(10) UNSIGNED NOT NULL COMMENT 'Which Domain is this match entry for' AFTER `id`;
    UPDATE `civicrm_membership_type` SET domain_id = @domain_id;
    ALTER TABLE `civicrm_membership_type`
        ADD CONSTRAINT `FK_civicrm_membership_type_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);
    
    ALTER TABLE `civicrm_menu`
        ADD `domain_id` INT(10) UNSIGNED NOT NULL COMMENT 'Which Domain is this match entry for' AFTER `id`;
    UPDATE `civicrm_menu` SET domain_id = @domain_id;
    ALTER TABLE `civicrm_menu`
        ADD CONSTRAINT `FK_civicrm_menu_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);
    
 -- CRM-4813

    ALTER TABLE `civicrm_menu`
    	DROP INDEX `UI_path`,
        ADD UNIQUE `UI_path_domain_id` ( `path`,`domain_id` );

    ALTER TABLE `civicrm_payment_processor`
        DROP INDEX `UI_name_test`,
        ADD UNIQUE `UI_name_test_domain_id` ( `name`,`is_test`,`domain_id` );

    ALTER TABLE `civicrm_preferences`
        ADD `domain_id` INT(10) UNSIGNED NOT NULL COMMENT 'Which Domain is this match entry for' AFTER `id`,
        DROP FOREIGN KEY `FK_civicrm_preferences_contact_id`;

    UPDATE `civicrm_preferences` SET domain_id = @domain_id;
    ALTER TABLE `civicrm_preferences`
        ADD CONSTRAINT `FK_civicrm_preferences_domain_id`  FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`) ON DELETE CASCADE,
        ADD CONSTRAINT `FK_civicrm_preferences_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
    
    ALTER TABLE `civicrm_uf_match`
        DROP FOREIGN KEY `FK_civicrm_uf_match_contact_id`,
        DROP INDEX `UI_uf_name` ,
        DROP INDEX `UI_contact` ;
    
    ALTER TABLE `civicrm_uf_match`
        ADD `domain_id` INT(10) UNSIGNED NOT NULL COMMENT 'Which Domain is this match entry for' AFTER `id`;
    UPDATE `civicrm_uf_match` SET domain_id = @domain_id;
    ALTER TABLE `civicrm_uf_match`
        ADD CONSTRAINT `FK_civicrm_uf_match_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`),
        ADD CONSTRAINT `FK_civicrm_uf_match_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
        ADD UNIQUE `UI_uf_name_domain_id` (`uf_name`,`domain_id`),
        ADD UNIQUE `UI_contact_domain_id` (`contact_id`,`domain_id`);
    
    ALTER TABLE `civicrm_mailing`
        ADD `created_date` datetime default NULL COMMENT 'Date and time this mailing was created.' AFTER `created_id`;
    
    ALTER TABLE `civicrm_contribution_page`
        ADD `created_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_contact, who created this contribution page',
        ADD `created_date` datetime default NULL COMMENT 'Date and time that contribution page was created.',
        ADD CONSTRAINT `FK_civicrm_contribution_page_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
    
    ALTER TABLE `civicrm_event`
        ADD `created_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_contact, who created this event',
        ADD `created_date` datetime default NULL COMMENT 'Date and time that event was created.',
        ADD CONSTRAINT `FK_civicrm_event_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

    -- CRM-4842
    ALTER TABLE `civicrm_mailing`
        DROP  FOREIGN KEY `FK_civicrm_mailing_created_id`,
  	DROP  FOREIGN KEY `FK_civicrm_mailing_scheduled_id`;

    ALTER TABLE `civicrm_mailing`
    	ADD CONSTRAINT `FK_civicrm_mailing_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
  	ADD CONSTRAINT `FK_civicrm_mailing_scheduled_id` FOREIGN KEY (`scheduled_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;
    
    -- CRM-4469
    -- Add collapse_adv_search column to civicrm_custom_group
    ALTER TABLE `civicrm_custom_group`
        ADD `collapse_adv_display` int(10) unsigned default '0' COMMENT 'Will this group be in collapsed or expanded mode on advanced search display ?' AFTER `max_multiple`,
        ADD `created_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_contact, who created this custom group',
        ADD `created_date` datetime default NULL COMMENT 'Date and time this custom group was created.',
        ADD CONSTRAINT `FK_civicrm_custom_group_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
    
    ALTER TABLE `civicrm_uf_group`
        ADD `created_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_contact, who created this UF group',
        ADD `created_date` datetime default NULL COMMENT 'Date and time this UF group was created.',
        ADD CONSTRAINT `FK_civicrm_uf_group_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
