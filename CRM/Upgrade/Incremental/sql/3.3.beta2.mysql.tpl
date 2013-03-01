-- CRM-6902, update system workflow message templates
{include file='../CRM/Upgrade/3.3.beta2.msg_template/civicrm_msg_template.tpl'}

-- CRM-6410, Create CiviMail Reports
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @mailCompId := max(id) FROM civicrm_component where name = 'CiviMail';
INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}description{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
VALUES
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Bounce Report{/ts}'{/localize}, 'Mailing/bounce', 'CRM_Report_Form_Mailing_Bounce', NULL, 0, NULL, 34, {localize}'{ts escape="sql"}Bounce Report for mailings{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Summary Report{/ts}'{/localize}, 'Mailing/summary', 'CRM_Report_Form_Mailing_Summary', NULL, 0, NULL, 35, {localize}'{ts escape="sql"}Summary statistics for mailings{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Opened Report{/ts}'{/localize}, 'Mailing/opened', 'CRM_Report_Form_Mailing_Opened', NULL, 0, NULL, 36, {localize}'{ts escape="sql"}Display contacts who opened emails from a mailing{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Clickthrough Report{/ts}'{/localize}, 'Mailing/clicks', 'CRM_Report_Form_Mailing_Clicks', NULL, 0, NULL, 37, {localize}'{ts escape="sql"}Display clicks from each mailing{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL);


-- CRM-7031

ALTER TABLE `civicrm_entity_financial_trxn`
     CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_financial_trxn`
     CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_pledge_payment`
     CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_contribution_recur`
 	 CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_product`
 	 CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_contribution_soft`
 	 CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_contribution_page`
 	 CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

-- CRM-7088
UPDATE  civicrm_navigation
   SET  label  = 'GOTV (Voter Tracking)'
 WHERE  name like 'Voter Listing';

--CRM-7067
ALTER TABLE `civicrm_participant`
   DROP FOREIGN KEY `FK_civicrm_participant_registered_by_id`;
ALTER TABLE `civicrm_participant`
    ADD CONSTRAINT `FK_civicrm_participant_registered_by_id` FOREIGN KEY (`registered_by_id`) REFERENCES `civicrm_participant` (`id`) ON DELETE SET NULL;