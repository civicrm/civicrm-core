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
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_udOpt, {localize}'{ts escape="sql"}Invoices / Credit Notes{/ts}'{/localize}, @option_group_id_udOpt_val+1, 'Invoices / Credit Notes', NULL, 0, NULL, @option_group_id_udOpt_wt+1, NULL, 0, 0, 1, NULL, NULL);

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

-- CRM-15453 mailing detail report template
SELECT @option_group_id_report := MAX(id)     FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @mailCompId       := MAX(id)     FROM civicrm_component where name = 'CiviContribute';
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Recurring Contributions Report'{/localize}, 'contribute/recur', 'CRM_Report_Form_Contribute_Recur', @weight := @weight + 1, {localize}'Shows information about the status of recurring contributions'{/localize}, 1, @mailCompId);

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( {$domainID}, 'Pending Recurring Contributions', 'contribute/recur', 'Shows all pending recurring contributions', 'access CiviContribute', '{literal}a:20:{s:8:"entryURL";s:58:"http://head.piglet/civicrm/report/contribute/recur?reset=1";s:6:"fields";a:6:{s:9:"sort_name";s:1:"1";s:6:"amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:18:"frequency_interval";s:1:"1";s:14:"frequency_unit";s:1:"1";s:12:"installments";s:1:"1";}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"5";}s:11:"currency_op";s:2:"in";s:14:"currency_value";a:0:{}s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:17:"frequency_unit_op";s:2:"in";s:20:"frequency_unit_value";a:0:{}s:9:"order_bys";a:1:{i:1;a:1:{s:6:"column";s:1:"-";}}s:11:"description";s:41:"Shows all pending recurring contributions";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:9:"row_count";s:0:"";s:14:"addToDashboard";s:1:"1";s:10:"permission";s:17:"access CiviReport";s:9:"parent_id";s:0:"";s:11:"instance_id";N;}{/literal}');
