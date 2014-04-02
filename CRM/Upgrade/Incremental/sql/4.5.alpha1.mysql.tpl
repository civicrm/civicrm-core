{* file to handle db changes in 4.5.alpha1 during upgrade *}
{include file='../CRM/Upgrade/4.5.alpha1.msg_template/civicrm_msg_template.tpl'}

ALTER TABLE `civicrm_contact`
  ADD COLUMN `formal_title` varchar(64) COMMENT 'Formal (academic or similar) title in front of name. (Prof., Dr. etc.)' AFTER `suffix_id`;

ALTER TABLE `civicrm_contact`
  ADD COLUMN `communication_style_id` int(10) unsigned COMMENT 'Communication style (e.g. formal vs. familiar) to use with this contact. FK to communication styles in civicrm_option_value.' AFTER `formal_title`,
  ADD INDEX `index_communication_style_id` (`communication_style_id`);

INSERT INTO
  `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`)
VALUES
  ('communication_style', {localize}'{ts escape="sql"}Communication Style{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_communication_style := max(id) from civicrm_option_group where name = 'communication_style';

INSERT INTO
  `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Formal{/ts}'{/localize}  , 1, 'formal'  , NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Familiar{/ts}'{/localize}, 2, 'familiar', NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL);

-- Insert menu item at Administer > Communications, above the various Greeting Formats

SELECT @parent_id := `id` FROM `civicrm_navigation` WHERE `name` = 'Communications' AND `domain_id` = {$domainID};
SELECT @add_weight := MIN(`weight`) FROM `civicrm_navigation` WHERE `name` IN('Email Greeting Formats', 'Postal Greeting Formats', 'Addressee Formats') AND `parent_id` = @parent_id;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` >= @add_weight;

INSERT INTO `civicrm_navigation`
  ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
  ( {$domainID}, 'civicrm/admin/options/communication_style&group=communication_style&reset=1', '{ts escape="sql" skip="true"}Communication Style Options{/ts}', 'Communication Style Options', 'administer CiviCRM', '', @parent_id, '1', NULL, @add_weight );

-- CRM-9988 Change world region of Panama country to America South, Central, North and Caribbean
UPDATE `civicrm_country` SET `region_id` = 2 WHERE `id` = 1166;

SELECT @option_group_id_contact_edit_options := max(id) from civicrm_option_group where name = 'contact_edit_options';

INSERT INTO
  `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Prefix{/ts}'{/localize}      , 12, 'Prefix'      , NULL, 2, NULL, 12, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Formal Title{/ts}'{/localize}, 13, 'Formal Title', NULL, 2, NULL, 13, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}First Name{/ts}'{/localize}  , 14, 'First Name'  , NULL, 2, NULL, 14, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Middle Name{/ts}'{/localize} , 15, 'Middle Name' , NULL, 2, NULL, 15, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Last Name{/ts}'{/localize}   , 16, 'Last Name'   , NULL, 2, NULL, 16, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Suffix{/ts}'{/localize}      , 17, 'Suffix'      , NULL, 2, NULL, 17, NULL, 0, 0, 1, NULL, NULL);

-- CRM-13857
ALTER TABLE civicrm_group
  ADD COLUMN `modified_id` INT(10) unsigned DEFAULT NULL COMMENT 'FK to contact table, modifier of the group.',
  ADD CONSTRAINT `FK_civicrm_group_modified_id` FOREIGN KEY (`modified_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL;

-- CRM-13913
ALTER TABLE civicrm_word_replacement
  ALTER COLUMN `is_active` SET DEFAULT 1;

--CRM-13833 Implement Soft Credit Type for Contribution
INSERT INTO civicrm_option_group
      (name, {localize field='title'}title{/localize}, is_reserved, is_active) VALUES ('soft_credit_type', {localize}'{ts escape="sql"}Soft Credit Types{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_soft_credit_type := max(id) from civicrm_option_group where name = 'soft_credit_type';

INSERT INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, `is_default`, `is_active`, `is_reserved`) 
 VALUES
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}In Honor of{/ts}'{/localize}, 1, 'in_honor_of', 1, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}In Memory of{/ts}'{/localize}, 2, 'in_memory_of', 2, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Solicited{/ts}'{/localize}, 3, 'solicited', 3, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Household{/ts}'{/localize}, 4, 'household', 4, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Workplace Giving{/ts}'{/localize}, 5, 'workplace', 5, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Foundation Affiliate{/ts}'{/localize}, 6, 'foundation_affiliate', 6, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}3rd-party Service{/ts}'{/localize}, 7, '3rd-party_service', 7, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Donor-advised Fund{/ts}'{/localize}, 8, 'donor-advised_fund', 8, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Matched Gift{/ts}'{/localize}, 9, 'matched_gift', 9, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Personal Campaign Page{/ts}'{/localize}, 10, 'pcp', 10, 0, 1, 1);

ALTER TABLE `civicrm_contribution_soft`
  ADD COLUMN `soft_credit_type_id`  int(10) unsigned COMMENT 'Soft Credit Type ID.Implicit FK to civicrm_option_value where option_group = soft_credit_type.';

SELECT @sct_pcp_id := value from civicrm_option_value where name = 'pcp' and option_group_id = @option_group_id_soft_credit_type;

UPDATE `civicrm_contribution_soft`
SET soft_credit_type_id = @sct_pcp_id
WHERE pcp_id IS NOT NULL;

--CRM-13734 make basic Case Activity Types reserved
SELECT @option_group_id_activity_type := id from civicrm_option_group where name = 'activity_type';
SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';

UPDATE `civicrm_option_value`
SET is_reserved = 1
WHERE is_reserved = 0 AND option_group_id = @option_group_id_activity_type AND component_id = @caseCompId;

-- CRM-13912
ALTER TABLE civicrm_action_schedule
ADD COLUMN `mode` varchar(128) COLLATE utf8_unicode_ci DEFAULT 'Email' COMMENT 'Send the message as email or sms or both.';

INSERT INTO
civicrm_option_group (name, {localize field='title'}title{/localize}, is_reserved, is_active)
VALUES
('msg_mode', {localize}'{ts escape="sql"}Message Mode{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_msg_mode := max(id) from civicrm_option_group where name = 'msg_mode';

INSERT INTO
civicrm_option_value (option_group_id, {localize field='label'}`label`{/localize}, value, name, is_default, weight, is_reserved, is_active)
VALUES
(@option_group_id_msg_mode, {localize}'{ts escape="sql"}Email{/ts}'{/localize}, 'Email', 'Email', 1, 1, 1, 1),
(@option_group_id_msg_mode, {localize}'{ts escape="sql"}SMS{/ts}'{/localize},'SMS', 'SMS', 0, 2, 1, 1),
(@option_group_id_msg_mode, {localize}'{ts escape="sql"}User Preference{/ts}'{/localize}, 'User_Preference', 'User Preference', 0, 3, 1, 1);

ALTER TABLE civicrm_action_schedule ADD sms_provider_id int(10) unsigned NULL COMMENT 'FK to civicrm_sms_provider id ';
ALTER TABLE civicrm_action_schedule ADD CONSTRAINT FK_civicrm_action_schedule_sms_provider_id FOREIGN KEY (`sms_provider_id`) REFERENCES `civicrm_sms_provider` (`id`) ON DELETE SET NULL;

--CRM-13981 migrate 'In Honor of' to Soft Credits
INSERT INTO `civicrm_uf_group`
     (`name`, `group_type`, {localize field='title'}`title`{/localize}, `is_cms_user`, `is_reserved`)
VALUES
   ('honoree_individual', 'Individual, Contact', {localize}'{ts escape="sql"}Honoree Individual{/ts}'{/localize}, 0, 1);

SELECT @uf_group_id_honoree_individual := id from civicrm_uf_group where name = 'honoree_individual';

INSERT INTO `civicrm_uf_field`
      (`uf_group_id`, `field_name`, `is_required`, `is_reserved`, `weight`, `visibility`, `in_selector`, `is_searchable`, `location_type_id`, {localize field='label'}`label`{/localize}, field_type)
VALUES
      (@uf_group_id_honoree_individual, 'prefix_id',  0, 1, 1, 'User and User Admin Only', 0, 1, NULL, '{ts escape="sql"}Individual Prefix{/ts}', 'Individual'),
      (@uf_group_id_honoree_individual, 'first_name', 0, 1, 2, 'User and User Admin Only', 0, 1, NULL, '{ts escape="sql"}First Name{/ts}',        'Individual'),
      (@uf_group_id_honoree_individual, 'last_name',  0, 1, 3, 'User and User Admin Only', 0, 1, NULL, '{ts escape="sql"}Last Name{/ts}',         'Individual'),
      (@uf_group_id_honoree_individual, 'email',      0, 1, 4, 'User and User Admin Only', 0, 1, 1,    '{ts escape="sql"}Email Address{/ts}',     'Individual');

ALTER TABLE `civicrm_uf_join`
  ADD COLUMN `module_data` varchar(255) COMMENT 'Json serialized array of data used by the ufjoin.module';

{if $multilingual}
  {foreach from=$locales item=loc}
     ALTER TABLE civicrm_contribution_page DROP honor_block_title_{$loc};
     ALTER TABLE civicrm_contribution_page DROP honor_block_text_{$loc};
  {/foreach}
{else}
     ALTER TABLE civicrm_contribution_page DROP honor_block_title;
     ALTER TABLE civicrm_contribution_page DROP honor_block_text;
{/if}
ALTER TABLE civicrm_contribution_page DROP honor_block_is_active;

ALTER TABLE civicrm_contribution DROP FOREIGN KEY `FK_civicrm_contribution_honor_contact_id`;
ALTER TABLE civicrm_contribution DROP honor_contact_id;
ALTER TABLE civicrm_contribution DROP honor_type_id;

ALTER TABLE civicrm_pledge DROP FOREIGN KEY `FK_civicrm_pledge_honor_contact_id`;
ALTER TABLE civicrm_pledge DROP honor_contact_id;
ALTER TABLE civicrm_pledge DROP honor_type_id;

-- CRM-13964 and CRM-13965
SELECT @option_group_id_cs   := max(id) from civicrm_option_group where name = 'contribution_status';
SELECT @option_val_id_cs_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cs;
SELECT @option_val_id_cs_val := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cs;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_cs, {localize}'{ts escape="sql"}Partially paid{/ts}'{/localize}, @option_val_id_cs_val+1, 'Partially paid', NULL, 0, NULL, @option_val_id_cs_wt+1, 0, 1, 1, NULL, NULL),
  (@option_group_id_cs, {localize}'{ts escape="sql"}Pending refund{/ts}'{/localize}, @option_val_id_cs_val+2, 'Pending refund', NULL, 0, NULL, @option_val_id_cs_wt+2, 0, 1, 1, NULL, NULL);

-- participant status adding
SELECT @participant_status_wt  := max(id) from civicrm_participant_status_type;

INSERT INTO civicrm_participant_status_type (name,  {localize field='label'}label{/localize}, class, is_reserved, is_active, is_counted, weight, visibility_id)
VALUES
  ('Partially paid', {localize}'{ts escape="sql"}Partially paid{/ts}'{/localize}, 'Positive', 1, 1, 1, @participant_status_wt+1, 2),
  ('Pending refund', {localize}'{ts escape="sql"}Pending refund{/ts}'{/localize}, 'Positive', 1, 1, 1, @participant_status_wt+2, 2);

-- new activity types required for partial payments
SELECT @option_group_id_act     := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @option_group_id_act_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @option_group_id_act_val := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_act, {localize}'{ts escape="sql"}Payment{/ts}'{/localize}, @option_group_id_act_val+1, 'Payment', NULL, 1, NULL, @option_group_id_act_wt+1, {localize}'{ts escape="sql"}Additional payment recorded for event or membership fee.{/ts}'{/localize}, 0, 1, 1, @contributeCompId, NULL),
   (@option_group_id_act, {localize}'{ts escape="sql"}Refund{/ts}'{/localize}, @option_group_id_act_val+2, 'Refund', NULL, 1, NULL, @option_group_id_act_wt+2, {localize}'{ts escape="sql"}Refund recorded for event or membership fee.{/ts}'{/localize}, 0, 1, 1, @contributeCompId, NULL),
   (@option_group_id_act, {localize}'{ts escape="sql"}Change Registration{/ts}'{/localize}, @option_group_id_act_val+3, 'Change Registration', NULL, 1, NULL, @option_group_id_act_wt+3, {localize}'{ts escape="sql"}Changes to an existing event registration.{/ts}'{/localize}, 0, 1, 1, @eventCompId, NULL);

-- CRM-13970
UPDATE civicrm_navigation set url = 'civicrm/admin/options/from_email_address&reset=1' WHERE url LIKE 'civicrm/admin/options/from_email%';
UPDATE civicrm_navigation set url = CONCAT(SUBSTRING_INDEX(url, '&', 1), '&reset=1') WHERE url LIKE 'civicrm/admin/options/%';

-- CRM-14181
ALTER TABLE  civicrm_acl CHANGE  operation  operation VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'What operation does this ACL entry control?';
ALTER TABLE  civicrm_campaign_group CHANGE  group_type  group_type VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Type of Group.';
ALTER TABLE  `civicrm_acl_contact_cache` CHANGE  `operation`  `operation` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'What operation does this user have permission on?';
ALTER TABLE  `civicrm_price_field` CHANGE  `html_type`  `html_type` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE  `civicrm_pledge` CHANGE  `frequency_unit`  `frequency_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'month' COMMENT  'Time units for recurrence of pledge payments.';
ALTER TABLE  `civicrm_membership_type` CHANGE  `duration_unit`  `duration_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Unit in which membership period is expressed.';
ALTER TABLE  `civicrm_membership_type` CHANGE  `period_type`  `period_type` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Rolling membership period starts on signup date. Fixed membership periods start on fixed_period_start_day.';
ALTER TABLE  `civicrm_membership_status` CHANGE  `start_event`  `start_event` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Event when this status starts.';
ALTER TABLE  `civicrm_membership_status` CHANGE  `start_event_adjust_unit`  `start_event_adjust_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Unit used for adjusting from start_event.';
ALTER TABLE  `civicrm_membership_status` CHANGE  `end_event`  `end_event` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Event after which this status ends.';
ALTER TABLE  `civicrm_membership_status` CHANGE  `end_event_adjust_unit`  `end_event_adjust_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Unit used for adjusting from the ending event.';
ALTER TABLE  `civicrm_mailing_job` CHANGE  `status`  `status` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'The state of this job';
ALTER TABLE  `civicrm_mailing_group` CHANGE  `group_type`  `group_type` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Are the members of the group included or excluded?.';
ALTER TABLE  `civicrm_mailing` CHANGE  `visibility`  `visibility` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'User and User Admin Only' COMMENT  'In what context(s) is the mailing contents visible (online viewing)';
ALTER TABLE  `civicrm_mailing_component` CHANGE  `component_type`  `component_type` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Type of Component.';
ALTER TABLE  `civicrm_mailing_bounce_type` CHANGE  `name`  `name` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'Type of bounce';
ALTER TABLE  `civicrm_participant_status_type` CHANGE  `class`  `class` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'the general group of status type this one belongs to';
ALTER TABLE  `civicrm_dedupe_rule_group` CHANGE  `contact_type`  `contact_type` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'The type of contacts this group applies to';
ALTER TABLE  `civicrm_dedupe_rule_group` CHANGE  `used`  `used` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Whether the rule should be used for cases where usage is Unsupervised, Supervised OR General(programatically)';
ALTER TABLE  `civicrm_word_replacement` CHANGE  `match_type`  `match_type` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'wildcardMatch';
ALTER TABLE  `civicrm_uf_field` CHANGE  `visibility`  `visibility` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'User and User Admin Only' COMMENT  'In what context(s) is this field visible.';
ALTER TABLE  `civicrm_mapping_field` CHANGE  `operator`  `operator` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'SQL WHERE operator for search-builder mapping fields (search criteria).';
ALTER TABLE  `civicrm_job` CHANGE  `run_frequency`  `run_frequency` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'Daily' COMMENT  'Scheduled job run frequency.';
ALTER TABLE  `civicrm_extension` CHANGE  `type`  `type` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE  `civicrm_custom_group` CHANGE  `style`  `style` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Visual relationship between this form and its parent.';
ALTER TABLE  `civicrm_custom_field` CHANGE  `data_type`  `data_type` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'Controls location of data storage in extended_data table.';
ALTER TABLE  `civicrm_custom_field` CHANGE  `html_type`  `html_type` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'HTML types plus several built-in extended types.';
ALTER TABLE  `civicrm_action_schedule` CHANGE  `start_action_unit`  `start_action_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Time units for reminder.';
ALTER TABLE  `civicrm_action_schedule` CHANGE  `repetition_frequency_unit`  `repetition_frequency_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Time units for repetition of reminder.';
ALTER TABLE  `civicrm_action_schedule` CHANGE  `end_frequency_unit`  `end_frequency_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Time units till repetition of reminder.';
ALTER TABLE  `civicrm_product` CHANGE  `period_type`  `period_type` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'rolling' COMMENT 'Rolling means we set start/end based on current day, fixed means we set start/end for current year or month(e.g. 1 year + fixed -> we would set start/end for 1/1/06 thru 12/31/06 for any premium chosen in 2006) ';
ALTER TABLE  `civicrm_product` CHANGE  `duration_unit`  `duration_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'year';
ALTER TABLE  `civicrm_product` CHANGE  `frequency_unit`  `frequency_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'month' COMMENT 'Frequency unit and interval allow option to store actual delivery frequency for a subscription or service.';
ALTER TABLE  `civicrm_contribution_recur` CHANGE  `frequency_unit`  `frequency_unit` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'month' COMMENT  'Time units for recurrence of payment.';
ALTER TABLE  `civicrm_subscription_history` CHANGE  `method`  `method` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'How the (un)subscription was triggered';
ALTER TABLE  `civicrm_subscription_history` CHANGE  `status`  `status` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'The state of the contact within the group';
ALTER TABLE  `civicrm_relationship_type` CHANGE  `contact_type_a`  `contact_type_a` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'If defined, contact_a in a relationship of this type must be a specific contact_type.';
ALTER TABLE  `civicrm_relationship_type` CHANGE  `contact_type_b`  `contact_type_b` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'If defined, contact_b in a relationship of this type must be a specific contact_type.';
ALTER TABLE  `civicrm_group_contact` CHANGE  `status`  `status` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'status of contact relative to membership in group';
ALTER TABLE  `civicrm_group` CHANGE  `visibility`  `visibility` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'User and User Admin Only' COMMENT  'In what context(s) is this field visible.';
ALTER TABLE  `civicrm_contact` CHANGE  `preferred_mail_format`  `preferred_mail_format` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT  'Both' COMMENT  'What is the preferred mode of sending an email.';

-- CRM-14183
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1157, "PL", "Plateau");
UPDATE civicrm_state_province SET name = "Abuja Federal Capital Territory" WHERE name = "Abuja Capital Territory";

-- CRM-13992
ALTER TABLE `civicrm_custom_field`
  ADD COLUMN `in_selector` tinyint(4) DEFAULT '0' COMMENT 'Should the multi-record custom field values be displayed in tab table listing';
UPDATE civicrm_custom_field cf
  LEFT JOIN civicrm_custom_group cg
    ON cf.custom_group_id = cg.id
  SET cf.in_selector = 1
  WHERE cg.is_multiple = 1 AND cf.html_type != 'TextArea';
ALTER TABLE `civicrm_custom_group`
 CHANGE COLUMN `style` `style` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Visual relationship between this form and its parent.';