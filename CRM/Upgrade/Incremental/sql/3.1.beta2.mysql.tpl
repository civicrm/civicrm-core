-- CRM-3507: upgrade message templates (if changed)
{include file='../CRM/Upgrade/3.1.beta2.msg_template/civicrm_msg_template.tpl'}

-- CRM-5496
    SELECT @option_group_id_report  := max(id) from civicrm_option_group where name = 'report_template';
    SELECT @caseCompId              := max(id) FROM civicrm_component where name = 'CiviCase';
    INSERT INTO
        `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}description{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
    VALUES
        (@option_group_id_report , {localize}'Case Summary Report'{/localize}        , 'case/summary'     , 'CRM_Report_Form_Case_Summary'     , NULL, 0, NULL, 24, {localize}'Provides a summary of cases and their duration by date range, status, staff member and / or case role.'{/localize}             , 0, 0, 1, @caseCompId, NULL),
        (@option_group_id_report , {localize}'Case Time Spent Report'{/localize}     , 'case/timespent'   , 'CRM_Report_Form_Case_TimeSpent'   , NULL, 0, NULL, 25, {localize}'Aggregates time spent on case and / or or non-case activities by activity type and contact.'{/localize}                        , 0, 0, 1, @caseCompId, NULL),
        (@option_group_id_report , {localize}'Contact Demographics Report'{/localize}, 'case/demographics', 'CRM_Report_Form_Case_Demographics', NULL, 0, NULL, 26, {localize}'Demographic breakdown for case clients (and or non-case contacts) in your database. Includes custom contact fields.'{/localize}, 0, 0, 1, @caseCompId, NULL),
        (@option_group_id_report , {localize}'Database Log Report'{/localize}        , 'contact/log'      , 'CRM_Report_Form_Contact_Log'      , NULL, 0, NULL, 27, {localize}'Log of contact and activity records created or updated in a given date range.'{/localize}                                      , 0, 0, 1, NULL       , NULL);
-- CRM-5438
UPDATE civicrm_navigation SET permission ='access CiviCRM', permission_operator ='' WHERE civicrm_navigation.name= 'Manage Groups';

-- CRM-5450

SELECT @option_group_id_address_options := max(id) from civicrm_option_group where name = 'address_options';
SELECT @adOpt_max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id = @option_group_id_address_options;
SELECT @adOpt_max_wt  := MAX(ROUND(val.weight)) FROM civicrm_option_value val where val.option_group_id = @option_group_id_address_options;

INSERT INTO
   civicrm_option_value(`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES(@option_group_id_address_options, {localize}'Street Address Parsing'{/localize}, (SELECT @adOpt_max_val := @adOpt_max_val+1), 'street_address_parsing', NULL, 0, NULL, (SELECT @adOpt_max_wt := @adOpt_max_wt + 1 ), 0, 0, 1, NULL, NULL);

--fix broken default address options.
SELECT  @domain_id := min(id) FROM civicrm_domain;

UPDATE  `civicrm_preferences`
   SET  `address_options` = REPLACE( `address_options`, '1314', '' )
 WHERE  `domain_id` = @domain_id
   AND  `contact_id` IS NULL;

-- CRM-5528

SELECT @option_group_id_cdt := max(id) from civicrm_option_group where name = 'custom_data_type';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`)
VALUES(@option_group_id_cdt, {localize}'Participant Event Type'{/localize}, '3', 'ParticipantEventType', NULL, 0, NULL, 3, 0, 0, 1);

-- add table dashboard and dashboard contact
-- CRM-5423

    CREATE TABLE civicrm_dashboard (
        id int(10)    unsigned NOT NULL auto_increment,
        domain_id    int(10) unsigned NOT NULL      COMMENT 'Domain for dashboard',
        {localize field='label'}label varchar(255)   COMMENT 'Widget Title' default NULL{/localize},
        url           varchar(255) default NULL      COMMENT 'url in case of external widget',
        content       text                           COMMENT 'widget content',
        permission    varchar(255)      default NULL COMMENT 'Permission for the widget',
        permission_operator varchar(3) default NULL COMMENT 'Permission Operator',
        column_no     tinyint(4)        default '0'  COMMENT 'column no for this widget',
        is_minimized  tinyint(4)        default '0'  COMMENT 'Is Minimized?',
        is_fullscreen tinyint(4)        default '1'  COMMENT 'Is Fullscreen?',
        is_active     tinyint(4)        default '0'  COMMENT 'Is this widget active?',
        weight        int(11)           default '0'  COMMENT 'Ordering of the widgets.',
        created_date  datetime          default NULL COMMENT 'When was content populated',
        PRIMARY KEY   (`id`),
        KEY `FK_civicrm_dashboard_domain_id` (`domain_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

    INSERT INTO civicrm_dashboard
        ( domain_id, {localize field='label'}`label`{/localize}, url, content, permission, permission_operator, column_no, is_minimized, is_fullscreen, is_active, weight, created_date )
    VALUES
        ( @domain_id, {localize }'Activities'{/localize}, 'civicrm/dashlet/activity&reset=1&snippet=4', NULL, NULL, NULL, 0, 0,'1', '1', NULL, NULL );

    CREATE TABLE civicrm_dashboard_contact (
        id int(10)    unsigned NOT NULL auto_increment,
        dashboard_id  int(10) unsigned NOT NULL    COMMENT 'Dashboard ID',
        contact_id    int(10) unsigned NOT NULL    COMMENT 'Contact ID',
        column_no     tinyint(4) default '0'       COMMENT 'column no for this widget',
        is_minimized  tinyint(4) default '0'       COMMENT 'Is Minimized?',
        is_fullscreen tinyint(4) default '1'       COMMENT 'Is Fullscreen?',
        is_active     tinyint(4) default '0'       COMMENT 'Is this widget active?',
        weight        int(11)    default '0'       COMMENT 'Ordering of the widgets.',
        PRIMARY KEY  (`id`),
        KEY `FK_civicrm_dashboard_contact_dashboard_id` (`dashboard_id`),
        KEY `FK_civicrm_dashboard_contact_contact_id` (`contact_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- CRM-5549

ALTER TABLE `civicrm_report_instance`
    ADD `domain_id` INT(10) UNSIGNED NOT NULL COMMENT 'Which Domain is this instance for' AFTER `id`;

UPDATE `civicrm_report_instance` SET domain_id = @domain_id;

ALTER TABLE `civicrm_report_instance`
    ADD CONSTRAINT `FK_civicrm_report_instance_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);

-- CRM-5546

ALTER TABLE `civicrm_price_set`
    ADD `domain_id` INT(10)  UNSIGNED DEFAULT NULL COMMENT 'Which Domain is this price-set for' AFTER `id`;
ALTER TABLE `civicrm_price_set`
    ADD CONSTRAINT `FK_civicrm_price_set_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);

ALTER TABLE `civicrm_option_value`
    ADD `domain_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Which Domain is this option value for' AFTER `component_id`;

ALTER TABLE `civicrm_option_value`
    ADD CONSTRAINT `FK_civicrm_option_value_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);

SELECT @option_group_id_grant := id from civicrm_option_group where name = 'grant_type';
SELECT @option_group_id_email := id from civicrm_option_group where name = 'from_email_address';

UPDATE `civicrm_option_value` SET domain_id = @domain_id WHERE option_group_id IN (@option_group_id_grant,@option_group_id_email );
