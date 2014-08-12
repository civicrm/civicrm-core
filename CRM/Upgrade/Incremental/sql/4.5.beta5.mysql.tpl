{* file to handle db changes in 4.5.beta5 during upgrade *}

{include file='../CRM/Upgrade/4.5.beta5.msg_template/civicrm_msg_template.tpl'}

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