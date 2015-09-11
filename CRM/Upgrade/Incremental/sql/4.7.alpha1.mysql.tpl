{* file to handle db changes in 4.7.alpha1 during upgrade *}

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
UPDATE civicrm_participant_status_type SET label = {localize field='label'}label = 'Pending (pay later)'{/localize} WHERE name = 'Pending from pay later';
UPDATE civicrm_participant_status_type SET label = {localize field='label'}label = 'Pending (incomplete transaction)'{/localize} WHERE name = 'Pending from incomplete transaction';

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

-- CRM-16876 Set country names to UPPERCASE
UPDATE civicrm_country SET `name` = UPPER( `name` );

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
   `civicrm_option_group` (`name`, `title`, `is_reserved`, `is_active`, `is_locked`)
   VALUES
   ('relative_date_filters'         , '{ts escape="sql"}Relative Date Filters{/ts}'              , 1, 1, 0);

SELECT @option_group_id_date_filter    := max(id) from civicrm_option_group where name = 'relative_date_filters';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
   VALUES
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 Years{/ts}', 'previous_2.year', 'previous_2.year', NULL, NULL, NULL,1, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 Quarters{/ts}', 'previous_2.quarter', 'previous_2.quarter', NULL, NULL, NULL,2, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 Months{/ts}', 'previous_2.month', 'previous_2.month', NULL, NULL, NULL,3, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 Weeks{/ts}', 'previous_2.week', 'previous_2.week', NULL, NULL, NULL,4, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 Days{/ts}', 'previous_2.day', 'previous_2.day', NULL, NULL, NULL,5, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Prior to Previous Year{/ts}', 'previous_before.year', 'previous_before.year', NULL, NULL, NULL,6, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Prior to Previous Quarter{/ts}', 'previous_before.quarter', 'previous_before.quarter', NULL, NULL, NULL,7, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Prior to Previous Month{/ts}', 'previous_before.month', 'previous_before.month', NULL, NULL, NULL,8, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Prior to Previous Week{/ts}', 'previous_before.week', 'previous_before.week', NULL, NULL, NULL,9, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Prior to Previous Day{/ts}', 'previous_before.day', 'previous_before.day', NULL, NULL, NULL,10, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Year{/ts}', 'previous.year', 'previous.year', NULL, NULL, NULL,11, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Fiscal Year{/ts}', 'previous.fiscal_year', 'previous.fiscal_year', NULL, NULL, NULL,12, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Quarter{/ts}', 'previous.quarter', 'previous.quarter', NULL, NULL, NULL,13, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Month{/ts}', 'previous.month', 'previous.month', NULL, NULL, NULL,14, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Week{/ts}', 'previous.week', 'previous.week', NULL, NULL, NULL,15, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Previous Year{/ts}', 'earlier.year', 'earlier.year', NULL, NULL, NULL,16, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Previous Quarter{/ts}', 'earlier.quarter', 'earlier.quarter', NULL, NULL, NULL,17, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Previous Month{/ts}', 'earlier.month', 'earlier.month', NULL, NULL, NULL,18, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Previous Week{/ts}', 'earlier.week', 'earlier.week', NULL, NULL, NULL,19, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Previous Day{/ts}', 'earlier.day', 'earlier.day', NULL, NULL, NULL,20, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From End of Previous Year{/ts}', 'greater_previous.year', 'greater_previous.year', NULL, NULL, NULL,21, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From End of Previous Quarter{/ts}', 'greater_previous.quarter', 'greater_previous.quarter', NULL, NULL, NULL,22, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From End of Previous Month{/ts}', 'greater_previous.month', 'greater_previous.month', NULL, NULL, NULL,23, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From End of Previous Week{/ts}', 'greater_previous.week', 'greater_previous.week', NULL, NULL, NULL,24, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From Start of Current Year{/ts}', 'greater.year', 'greater.year', NULL, NULL, NULL,25, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From Start of Current Quarter{/ts}', 'greater.quarter', 'greater.quarter', NULL, NULL, NULL,26, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From Start of Current Month{/ts}', 'greater.month', 'greater.month', NULL, NULL, NULL,27, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From Start of Current Week{/ts}', 'greater.week', 'greater.week', NULL, NULL, NULL,28, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From Start of Current Day{/ts}', 'greater.day', 'greater.day', NULL, NULL, NULL,29, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current Year to-date{/ts}', 'current.year', 'current.year', NULL, NULL, NULL,30, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current Quarter to-date{/ts}', 'current.quarter', 'current.quarter', NULL, NULL, NULL,31, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current Month to-date{/ts}', 'current.month', 'current.month', NULL, NULL, NULL,32, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current Week to-date{/ts}', 'current.week', 'current.week', NULL, NULL, NULL,33, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 3 Years{/ts}', 'ending_3.year', 'ending_3.year', NULL, NULL, NULL,34, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 2 Years{/ts}', 'ending_2.year', 'ending_2.year', NULL, NULL, NULL,35, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 12 Months{/ts}', 'ending.year', 'ending.year', NULL, NULL, NULL,36, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 3 Months{/ts}', 'ending.quarter', 'ending.quarter', NULL, NULL, NULL,37, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last Month{/ts}', 'ending.month', 'ending.month', NULL, NULL, NULL,38, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 7 days{/ts}', 'ending.week', 'ending.week', NULL, NULL, NULL,39, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Yesterday{/ts}', 'previous.day', 'previous.day', NULL, NULL, NULL,40, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Year{/ts}', 'this.year', 'this.year', NULL, NULL, NULL,41, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Fiscal Year{/ts}', 'this.fiscal_year', 'this.fiscal_year', NULL, NULL, NULL,42, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Quarter{/ts}', 'this.quarter', 'this.quarter', NULL, NULL, NULL,43, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Month{/ts}', 'this.month', 'this.month', NULL, NULL, NULL,44, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Week{/ts}', 'this.week', 'this.week', NULL, NULL, NULL,45, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Today{/ts}', 'this.day', 'this.day', NULL, NULL, NULL,46, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Tomorrow{/ts}', 'starting.day', 'starting.day', NULL, NULL, NULL,47, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Upcoming 7 days{/ts}', 'starting.week', 'starting.week', NULL, NULL, NULL,48, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Upcoming Month{/ts}', 'starting.month', 'starting.month', NULL, NULL, NULL,49, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Upcoming 12 Months{/ts}', 'starting.year', 'starting.year', NULL, NULL, NULL,50, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Current Year{/ts}', 'less.year', 'less.year', NULL, NULL, NULL,51, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Current Quarter{/ts}', 'less.quarter', 'less.quarter', NULL, NULL, NULL,52, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Current Month{/ts}', 'less.month', 'less.month', NULL, NULL, NULL,53, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To End of Current Week{/ts}', 'less.week', 'less.week', NULL, NULL, NULL,54, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next Week{/ts}', 'next.week', 'next.week', NULL, NULL, NULL,55, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next Month{/ts}', 'next.month', 'next.month', NULL, NULL, NULL,56, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next Quarter{/ts}', 'next.quarter', 'next.quarter', NULL, NULL, NULL,57, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next Fiscal Year{/ts}', 'next.fiscal_year', 'next.fiscal_year', NULL, NULL, NULL,58, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next Year{/ts}', 'next.year', 'next.year', NULL, NULL, NULL,59, NULL, 0, 0, 1, NULL, NULL);

-- CRM-16873
{if $multilingual}
  {foreach from=$locales item=loc}
     ALTER TABLE civicrm_contribution_page DROP for_organization_{$loc};
  {/foreach}
{else}
     ALTER TABLE civicrm_contribution_page DROP for_organization;
{/if}
ALTER TABLE civicrm_contribution_page DROP is_for_organization;
