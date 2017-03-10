{* file to handle db changes in 4.7.alpha1 during upgrade *}

-- Add new columns for multilingual purpose
ALTER TABLE `civicrm_action_schedule` ADD COLUMN `filter_contact_language` varchar(128) DEFAULT NULL COMMENT 'Used for multilingual installation';
ALTER TABLE `civicrm_action_schedule` ADD COLUMN `communication_language` varchar(8) DEFAULT NULL COMMENT 'Used for multilingual installation';
ALTER TABLE `civicrm_action_schedule` MODIFY COLUMN mapping_id varchar(64);
-- Q: Should we validate that local civicrm_action_mapping records have expected IDs?

-- CRM-16354
SELECT @option_group_id_wysiwyg := max(id) from civicrm_option_group where name = 'wysiwyg_editor';

UPDATE civicrm_option_value SET name = 'Textarea', {localize field='label'}label = 'Textarea'{/localize}
  WHERE value = 1 AND option_group_id = @option_group_id_wysiwyg;

DELETE FROM civicrm_option_value WHERE name IN ('Joomla Default Editor', 'Drupal Default Editor')
  AND option_group_id = @option_group_id_wysiwyg;

UPDATE civicrm_option_value SET is_active = 1, is_reserved = 1 WHERE option_group_id = @option_group_id_wysiwyg;

--CRM-16719
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';

UPDATE civicrm_option_value SET {localize field="label"}label = 'Activity Details Report'{/localize}
  WHERE value = 'activity' AND option_group_id = @option_group_id_report;

UPDATE civicrm_option_value SET {localize field="label"}label = 'Activity Summary Report'{/localize}
  WHERE value = 'activitySummary' AND option_group_id = @option_group_id_report;

--CRM-11369
UPDATE civicrm_participant_status_type SET {localize field='label'}label = 'Pending (pay later)'{/localize} WHERE name = 'Pending from pay later';
UPDATE civicrm_participant_status_type SET {localize field='label'}label = 'Pending (incomplete transaction)'{/localize} WHERE name = 'Pending from incomplete transaction';

--CRM-16853 PCP Owner Notification
--CRM-16853 Contribution Invoice Receipt Translation

{include file='../CRM/Upgrade/4.7.alpha1.msg_template/civicrm_msg_template.tpl'}

-- CRM-16478 Remove custom fatal error template path
DELETE FROM civicrm_setting WHERE name = 'fatalErrorTemplate';

UPDATE civicrm_state_province SET name = 'Bataan' WHERE name = 'Batasn';

--CRM-16914
ALTER TABLE civicrm_payment_processor
ADD COLUMN
`payment_instrument_id` int unsigned   DEFAULT 1 COMMENT 'Payment Instrument ID';

ALTER TABLE civicrm_payment_processor_type
ADD COLUMN
`payment_instrument_id` int unsigned   DEFAULT 1 COMMENT 'Payment Instrument ID';

-- CRM-16447
UPDATE civicrm_state_province SET name = 'Northern Ostrobothnia' WHERE name = 'Nothern Ostrobothnia';

-- CRM-14078
UPDATE civicrm_option_group SET {localize field="title"}title = '{ts escape="sql"}Payment Methods{/ts}'{/localize} WHERE name = 'payment_instrument';
UPDATE civicrm_navigation SET label = '{ts escape="sql"}Payment Methods{/ts}' WHERE name = 'Payment Instruments';

-- CRM-16176
{if $multilingual}
  {foreach from=$locales item=locale}
     ALTER TABLE civicrm_relationship_type ADD label_a_b_{$locale} varchar(64);
     ALTER TABLE civicrm_relationship_type ADD label_b_a_{$locale} varchar(64);
     ALTER TABLE civicrm_relationship_type ADD description_{$locale} varchar(255);

     UPDATE civicrm_relationship_type SET label_a_b_{$locale} = label_a_b;
     UPDATE civicrm_relationship_type SET label_b_a_{$locale} = label_b_a;
     UPDATE civicrm_relationship_type SET description_{$locale} = description;
  {/foreach}

  ALTER TABLE civicrm_relationship_type DROP label_a_b;
  ALTER TABLE civicrm_relationship_type DROP label_b_a;
  ALTER TABLE civicrm_relationship_type DROP description;
{/if}

-- CRM-13283
CREATE TABLE IF NOT EXISTS `civicrm_status_pref` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique Status Preference ID',
     `domain_id` int unsigned NOT NULL   COMMENT 'Which Domain is this Status Preference for',
     `name` varchar(255) NOT NULL   COMMENT 'Name of the status check this preference references.',
     `hush_until` date   DEFAULT NULL COMMENT 'expires ignore_severity.  NULL never hushes.',
     `ignore_severity` int unsigned   DEFAULT 1 COMMENT 'Hush messages up to and including this severity.',
     `prefs` varchar(255)    COMMENT 'These settings are per-check, and can\'t be compared across checks.',
     `check_info` varchar(255)    COMMENT 'These values are per-check, and can\'t be compared across checks.'
,
    PRIMARY KEY ( `id` )

    ,     INDEX `UI_status_pref_name`(
        name
  )

,          CONSTRAINT FK_civicrm_status_pref_domain_id FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain`(`id`)
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- CRM-17005
UPDATE civicrm_country SET name = 'PALESTINIAN TERRITORY' WHERE name = 'PALESTINIAN TERRITORY, OCCUPIED';

-- CRM-17145 update Activity detail data type
ALTER TABLE `civicrm_activity` CHANGE `details` `details` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Details about the activity (agenda, notes, etc).';

-- CRM-17136 Added Punjab Province for Pakistan
INSERT IGNORE INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
(NULL, 1163, "PB", "Punjab");

-- CRM-16195: Move relative date filters from code to database
INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`, `is_locked`)
   VALUES
   ('relative_date_filters'         , {localize}'{ts escape="sql"}Relative Date Filters{/ts}'{/localize}              , 1, 1, 0);

SELECT @option_group_id_date_filter    := max(id) from civicrm_option_group where name = 'relative_date_filters';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
   VALUES
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Today{/ts}'{/localize}, 'this.day', 'this.day', NULL, NULL, NULL,1, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}This week{/ts}'{/localize}, 'this.week', 'this.week', NULL, NULL, NULL,2, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}This calendar month{/ts}'{/localize}, 'this.month', 'this.month', NULL, NULL, NULL,3, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}This quarter{/ts}'{/localize}, 'this.quarter', 'this.quarter', NULL, NULL, NULL,4, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}This fiscal year{/ts}'{/localize}, 'this.fiscal_year', 'this.fiscal_year', NULL, NULL, NULL,5, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}This calendar year{/ts}'{/localize}, 'this.year', 'this.year', NULL, NULL, NULL,6, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Yesterday{/ts}'{/localize}, 'previous.day', 'previous.day', NULL, NULL, NULL,7, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous week{/ts}'{/localize}, 'previous.week', 'previous.week', NULL, NULL, NULL,8, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous calendar month{/ts}'{/localize}, 'previous.month', 'previous.month', NULL, NULL, NULL,9, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous quarter{/ts}'{/localize}, 'previous.quarter', 'previous.quarter', NULL, NULL, NULL,10, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous fiscal year{/ts}'{/localize}, 'previous.fiscal_year', 'previous.fiscal_year', NULL, NULL, NULL,11, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous calendar year{/ts}'{/localize}, 'previous.year', 'previous.year', NULL, NULL, NULL,12, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 7 days including today{/ts}'{/localize}, 'ending.week', 'ending.week', NULL, NULL, NULL,13, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 30 days including today{/ts}'{/localize}, 'ending.month', 'ending.month', NULL, NULL, NULL,14, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 60 days including today{/ts}'{/localize}, 'ending_2.month', 'ending_2.month', NULL, NULL, NULL,15, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 90 days including today{/ts}'{/localize}, 'ending.quarter', 'ending.quarter', NULL, NULL, NULL,16, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 12 months including today{/ts}'{/localize}, 'ending.year', 'ending.year', NULL, NULL, NULL,17, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 2 years including today{/ts}'{/localize}, 'ending_2.year', 'ending_2.year', NULL, NULL, NULL,18, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 3 years including today{/ts}'{/localize}, 'ending_3.year', 'ending_3.year', NULL, NULL, NULL,19, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Tomorrow{/ts}'{/localize}, 'starting.day', 'starting.day', NULL, NULL, NULL,20, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next week{/ts}'{/localize}, 'next.week', 'next.week', NULL, NULL, NULL,21, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next calendar month{/ts}'{/localize}, 'next.month', 'next.month', NULL, NULL, NULL,22, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next quarter{/ts}'{/localize}, 'next.quarter', 'next.quarter', NULL, NULL, NULL,23, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next fiscal year{/ts}'{/localize}, 'next.fiscal_year', 'next.fiscal_year', NULL, NULL, NULL,24, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next calendar year{/ts}'{/localize}, 'next.year', 'next.year', NULL, NULL, NULL,25, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next 7 days including today{/ts}'{/localize}, 'starting.week', 'starting.week', NULL, NULL, NULL,26, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next 30 days including today{/ts}'{/localize}, 'starting.month', 'starting.month', NULL, NULL, NULL,27, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next 60 days including today{/ts}'{/localize}, 'starting_2.month', 'starting_2.month', NULL, NULL, NULL,28, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next 90 days including today{/ts}'{/localize}, 'starting.quarter', 'starting.quarter', NULL, NULL, NULL,29, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Next 12 months including today{/ts}'{/localize}, 'starting.year', 'starting.year', NULL, NULL, NULL,30, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Current week to-date{/ts}'{/localize}, 'current.week', 'current.week', NULL, NULL, NULL,31, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Current calendar month to-date{/ts}'{/localize}, 'current.month', 'current.month', NULL, NULL, NULL,32, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Current quarter to-date{/ts}'{/localize}, 'current.quarter', 'current.quarter', NULL, NULL, NULL,33, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Current calendar year to-date{/ts}'{/localize}, 'current.year', 'current.year', NULL, NULL, NULL,34, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of yesterday{/ts}'{/localize}, 'earlier.day', 'earlier.day', NULL, NULL, NULL,35, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of previous week{/ts}'{/localize}, 'earlier.week', 'earlier.week', NULL, NULL, NULL,36, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of previous calendar month{/ts}'{/localize}, 'earlier.month', 'earlier.month', NULL, NULL, NULL,37, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of previous quarter{/ts}'{/localize}, 'earlier.quarter', 'earlier.quarter', NULL, NULL, NULL,38, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of previous calendar year{/ts}'{/localize}, 'earlier.year', 'earlier.year', NULL, NULL, NULL,39, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From start of current day{/ts}'{/localize}, 'greater.day', 'greater.day', NULL, NULL, NULL,40, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From start of current week{/ts}'{/localize}, 'greater.week', 'greater.week', NULL, NULL, NULL,41, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From start of current calendar month{/ts}'{/localize}, 'greater.month', 'greater.month', NULL, NULL, NULL,42, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From start of current quarter{/ts}'{/localize}, 'greater.quarter', 'greater.quarter', NULL, NULL, NULL,43, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From start of current calendar year{/ts}'{/localize}, 'greater.year', 'greater.year', NULL, NULL, NULL,44, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of current week{/ts}'{/localize}, 'less.week', 'less.week', NULL, NULL, NULL,45, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of current calendar month{/ts}'{/localize}, 'less.month', 'less.month', NULL, NULL, NULL,46, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of current quarter{/ts}'{/localize}, 'less.quarter', 'less.quarter', NULL, NULL, NULL,47, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of current calendar year{/ts}'{/localize}, 'less.year', 'less.year', NULL, NULL, NULL,48, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 days{/ts}'{/localize}, 'previous_2.day', 'previous_2.day', NULL, NULL, NULL,49, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 weeks{/ts}'{/localize}, 'previous_2.week', 'previous_2.week', NULL, NULL, NULL,50, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 calendar months{/ts}'{/localize}, 'previous_2.month', 'previous_2.month', NULL, NULL, NULL,51, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 quarters{/ts}'{/localize}, 'previous_2.quarter', 'previous_2.quarter', NULL, NULL, NULL,52, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 calendar years{/ts}'{/localize}, 'previous_2.year', 'previous_2.year', NULL, NULL, NULL,53, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Day prior to yesterday{/ts}'{/localize}, 'previous_before.day', 'previous_before.day', NULL, NULL, NULL,54, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Week prior to previous week{/ts}'{/localize}, 'previous_before.week', 'previous_before.week', NULL, NULL, NULL,55, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Month prior to previous calendar month{/ts}'{/localize}, 'previous_before.month', 'previous_before.month', NULL, NULL, NULL,56, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Quarter prior to previous quarter{/ts}'{/localize}, 'previous_before.quarter', 'previous_before.quarter', NULL, NULL, NULL,57, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Year prior to previous calendar year{/ts}'{/localize}, 'previous_before.year', 'previous_before.year', NULL, NULL, NULL,58, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From end of previous week{/ts}'{/localize}, 'greater_previous.week', 'greater_previous.week', NULL, NULL, NULL,59, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From end of previous calendar month{/ts}'{/localize}, 'greater_previous.month', 'greater_previous.month', NULL, NULL, NULL,60, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From end of previous quarter{/ts}'{/localize}, 'greater_previous.quarter', 'greater_previous.quarter', NULL, NULL, NULL,61, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}From end of previous calendar year{/ts}'{/localize}, 'greater_previous.year', 'greater_previous.year', NULL, NULL, NULL,62, 0, 0, 1, NULL, NULL);

-- CRM-16873
{if $multilingual}
  {foreach from=$locales item=loc}
     ALTER TABLE civicrm_contribution_page DROP for_organization_{$loc};
  {/foreach}
{else}
     ALTER TABLE civicrm_contribution_page DROP for_organization;
{/if}
ALTER TABLE civicrm_contribution_page DROP is_for_organization;
