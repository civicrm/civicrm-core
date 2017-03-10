{* file to handle db changes in 4.6.alpha1 during upgrade *}

{include file='../CRM/Upgrade/4.6.alpha1.msg_template/civicrm_msg_template.tpl'}

-- Financial account relationship
SELECT @option_group_id_arel           := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_arel_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel;
SELECT @option_group_id_arel_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel;
INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_arel, {localize}'{ts escape="sql"}Sales Tax Account is{/ts}'{/localize}, @option_group_id_arel_val+1, 'Sales Tax Account is', NULL, 0, 0, @option_group_id_arel_wt+1, {localize}'Sales Tax Account is'{/localize}, 0, 1, 1, 2, NULL);

-- Add new column tax_amount in contribution and lineitem table
ALTER TABLE `civicrm_contribution` ADD `tax_amount` DECIMAL( 20, 2 ) DEFAULT NULL COMMENT 'Total tax amount of this contribution.';
ALTER TABLE `civicrm_line_item` ADD `tax_amount` DECIMAL( 20, 2 ) DEFAULT NULL COMMENT 'tax of each item';

-- Insert menu item at Administer > CiviContribute, below the Payment Processors.
SELECT @parent_id := id from `civicrm_navigation` where name = 'CiviContribute' AND domain_id = {$domainID};
SELECT @add_weight_id := weight from `civicrm_navigation` where `name` = 'Payment Processors' and `parent_id` = @parent_id;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` > @add_weight_id;

INSERT INTO `civicrm_navigation`
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
  ( {$domainID}, 'civicrm/admin/setting/preferences/contribute',      '{ts escape="sql" skip="true"}CiviContribute Component Settings{/ts}', 'CiviContribute Component Settings', 'administer CiviCRM', '', @parent_id, '1', NULL, @add_weight_id + 1 );

CREATE TABLE IF NOT EXISTS `civicrm_mailing_abtest` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  ,
     `name` varchar(128)    COMMENT 'Name of the A/B test',
     `status` varchar(32)    COMMENT 'Status',
     `mailing_id_a` int unsigned    COMMENT 'The first experimental mailing (\"A\" condition)',
     `mailing_id_b` int unsigned    COMMENT 'The second experimental mailing (\"B\" condition)',
     `mailing_id_c` int unsigned    COMMENT 'The final, general mailing (derived from A or B)',
     `domain_id` int unsigned    COMMENT 'Which site is this mailing for',
     `testing_criteria_id` int unsigned    ,
     `winner_criteria_id` int unsigned    ,
     `specific_url` varchar(255)    COMMENT 'What specific url to track',
     `declare_winning_time` datetime    COMMENT 'In how much time to declare winner',
     `group_percentage` int unsigned
,
    PRIMARY KEY ( `id` )
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- Insert menu items under "Mailings" for A/B Tests
SELECT @parent_id := id from `civicrm_navigation` where name = 'Mailings' AND domain_id = {$domainID};
SELECT @add_weight_id := weight from `civicrm_navigation` where `name` = 'Find Mass SMS' and `parent_id` = @parent_id;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+2
WHERE `parent_id` = @parent_id
AND `weight` > @add_weight_id;

INSERT INTO `civicrm_navigation`
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/a/#/abtest/new',                            '{ts escape="sql" skip="true"}New A/B Test{/ts}', 'New A/B Test',                                        'access CiviMail,create mailings', 'OR', @parent_id  , '1', NULL, @add_weight_id + 1 ),
( {$domainID}, 'civicrm/a/#/abtest',                                '{ts escape="sql" skip="true"}Manage A/B Tests{/ts}', 'Manage A/B Tests',                                'access CiviMail,create mailings', 'OR', @parent_id, '1', 1, @add_weight_id + 2 );


-- New activity types required for Print and Email Invoice
SELECT @option_group_id_act     := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @option_group_id_act_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @option_group_id_act_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_act, {localize}'{ts escape="sql"}Downloaded Invoice{/ts}'{/localize}, @option_group_id_act_val+1, 'Downloaded Invoice', NULL, 1, NULL, @option_group_id_act_wt+1, {localize}'{ts escape="sql"}Downloaded Invoice.{/ts}'{/localize}, 0, 1, 1, NULL, NULL),
   (@option_group_id_act, {localize}'{ts escape="sql"}Emailed Invoice{/ts}'{/localize}, @option_group_id_act_val+2, 'Emailed Invoice', NULL, 1, NULL, @option_group_id_act_wt+2, {localize}'{ts escape="sql"}Emailed Invoice.{/ts}'{/localize}, 0, 1, 1, NULL, NULL);

-- New option for Contact Dashboard
SELECT @option_group_id_udOpt     := max(id) from civicrm_option_group where name = 'user_dashboard_options';
SELECT @option_group_id_udOpt_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_udOpt;
SELECT @option_group_id_udOpt_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_udOpt;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_udOpt, {localize}'{ts escape="sql"}Invoices / Credit Notes{/ts}'{/localize}, @option_group_id_udOpt_val+1, 'Invoices / Credit Notes', NULL, 0, NULL, @option_group_id_udOpt_wt+1, 0, 0, 1, NULL, NULL);

-- Add new column creditnote_id in contribution table
ALTER TABLE `civicrm_contribution` ADD `creditnote_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'unique credit note id, system generated or passed in';

-- Add new column is_billing_required in contribution_page and event table
ALTER TABLE `civicrm_event` ADD COLUMN `is_billing_required` tinyint(4) DEFAULT '0' COMMENT 'Billing block required for Event';
ALTER TABLE `civicrm_contribution_page` ADD COLUMN `is_billing_required` tinyint(4) DEFAULT '0' COMMENT 'Billing block required for Contribution Page';

-- CRM-15256
ALTER TABLE civicrm_action_schedule ADD used_for VARCHAR(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Used for repeating entity' AFTER sms_provider_id;

CREATE TABLE IF NOT EXISTS `civicrm_recurring_entity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'primary key',
  `parent_id` int(10) unsigned NOT NULL COMMENT 'recurring entity parent id',
  `entity_id` int(10) unsigned DEFAULT NULL COMMENT 'recurring entity child id',
  `entity_table` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'physical tablename for entity, e.g. civicrm_event',
  `mode` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-this entity, 2-this and the following entities, 3-all the entities',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=87 ;

-- add batch type for pledge payments
SELECT @option_group_id := id FROM civicrm_option_group WHERE name = 'batch_type';

SELECT @max_option_value:= max(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id;

INSERT INTO civicrm_option_value(option_group_id, {localize field='label'}`label`{/localize}, value, name,weight)
VALUES (@option_group_id, {localize}'{ts escape="sql"}Pledge Payment{/ts}'{/localize}, @max_option_value+1, 'Pledge Payment','3');

--CRM-12281: To update name of Latvian provinces.
UPDATE `civicrm_state_province` SET `name` = (N'Jūrmala') where `id` = 3552;
UPDATE `civicrm_state_province` SET `name` = (N'Liepāja') WHERE `id` = 3553;
UPDATE `civicrm_state_province` SET `name` = (N'Rēzekne') WHERE `id` = 3554;
UPDATE `civicrm_state_province` SET `name` = (N'Rīga') WHERE `id` = 3555;

--CRM-15361: Allow selection of location type when sending bulk email
ALTER TABLE civicrm_mailing ADD COLUMN location_type_id INT(10) unsigned DEFAULT 0 COMMENT 'With email_selection_method, determines which email address to use';
ALTER TABLE civicrm_mailing ADD COLUMN email_selection_method varchar(20) DEFAULT 'automatic' COMMENT 'With location_type_id, determine how to choose the email address to use.';

-- CRM-15500 fix
ALTER TABLE `civicrm_action_schedule` CHANGE `limit_to` `limit_to` TINYINT( 4 ) NULL DEFAULT NULL;

-- CRM-15453 Recurring Contributions report template AND instance
SELECT @option_group_id_report := MAX(id) FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @contribCompId := MAX(id) FROM civicrm_component where name = 'CiviContribute';
INSERT INTO civicrm_option_value
(option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
(@option_group_id_report, {localize}'Recurring Contributions Report'{/localize}, 'contribute/recur', 'CRM_Report_Form_Contribute_Recur', @weight := @weight + 1, {localize}'Shows information about the status of recurring contributions'{/localize}, 1, @contribCompId);
INSERT INTO `civicrm_report_instance`
( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
( {$domainID}, 'Pending Recurring Contributions', 'contribute/recur', 'Shows all pending recurring contributions', 'access CiviContribute', '{literal}a:39:{s:6:"fields";a:7:{s:9:"sort_name";s:1:"1";s:6:"amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:18:"frequency_interval";s:1:"1";s:14:"frequency_unit";s:1:"1";s:12:"installments";s:1:"1";s:8:"end_date";s:1:"1";}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"5";}s:11:"currency_op";s:2:"in";s:14:"currency_value";a:0:{}s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:17:"frequency_unit_op";s:2:"in";s:20:"frequency_unit_value";a:0:{}s:22:"frequency_interval_min";s:0:"";s:22:"frequency_interval_max";s:0:"";s:21:"frequency_interval_op";s:3:"lte";s:24:"frequency_interval_value";s:0:"";s:16:"installments_min";s:0:"";s:16:"installments_max";s:0:"";s:15:"installments_op";s:3:"lte";s:18:"installments_value";s:0:"";s:19:"start_date_relative";s:0:"";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:37:"next_sched_contribution_date_relative";s:0:"";s:33:"next_sched_contribution_date_from";s:0:"";s:31:"next_sched_contribution_date_to";s:0:"";s:17:"end_date_relative";s:0:"";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:28:"calculated_end_date_relative";s:0:"";s:24:"calculated_end_date_from";s:0:"";s:22:"calculated_end_date_to";s:0:"";s:9:"order_bys";a:1:{i:1;a:1:{s:6:"column";s:1:"-";}}s:11:"description";s:41:"Shows all pending recurring contributions";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:9:"row_count";s:0:"";s:14:"addToDashboard";s:1:"1";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:11:"instance_id";N;}{/literal}');

-- CRM-15557--
ALTER TABLE civicrm_line_item MODIFY COLUMN qty decimal(20,2);

-- CRM-15740
ALTER TABLE `civicrm_mailing_trackable_url` CHANGE `url` `url` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'The URL to be tracked.';

-- CRM-15765 missing Indonesian provinces and revise outdated names
INSERT INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`)
VALUES (NULL, 1102, "SR", "Sulawesi Barat"), (NULL, 1102, "KT", "Kalimantan Tengah"), (NULL, 1102, "KU", "Kalimantan Utara");

UPDATE `civicrm_state_province` SET `name`='Kepulauan Bangka Belitung' WHERE `id` = 3056;
UPDATE `civicrm_state_province` SET `name`='Papua Barat', `abbreviation`='PB' WHERE `id` = 3060;
UPDATE `civicrm_state_province` SET `name`='DKI Jakarta' WHERE `id` = 3083;
UPDATE `civicrm_state_province` SET `name`='DI Yogyakarta' WHERE `id` = 3085;
UPDATE `civicrm_state_province` SET `abbreviation`='KI' WHERE `id` = 3066;

-- CRM-15203 Handle MembershipPayment records while upgrade
INSERT INTO civicrm_membership_payment (contribution_id, membership_id) select cc.id, cm.id FROM civicrm_contribution cc LEFT JOIN civicrm_membership_payment cmp ON cc.id = cmp.contribution_id LEFT JOIN civicrm_membership cm ON cc.contribution_recur_id = cm.contribution_recur_id WHERE cc.contribution_recur_id IS NOT NULL AND cmp.id IS NULL AND cm.id IS NOT NULL;
