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
  `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Formal{/ts}'{/localize},   1, 'formal'  , NULL, 0, 1, 1, 0, 0, 1, NULL, NULL),
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Familiar{/ts}'{/localize}, 2, 'familiar', NULL, 0, 0, 2, 0, 0, 1, NULL, NULL);

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
  `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Prefix{/ts}'{/localize}      , 12, 'Prefix'      , NULL, 2, NULL, 12, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Formal Title{/ts}'{/localize}, 13, 'Formal Title', NULL, 2, NULL, 13, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}First Name{/ts}'{/localize}  , 14, 'First Name'  , NULL, 2, NULL, 14, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Middle Name{/ts}'{/localize} , 15, 'Middle Name' , NULL, 2, NULL, 15, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Last Name{/ts}'{/localize}   , 16, 'Last Name'   , NULL, 2, NULL, 16, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Suffix{/ts}'{/localize}      , 17, 'Suffix'      , NULL, 2, NULL, 17, 0, 0, 1, NULL, NULL);

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
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Personal Campaign Page{/ts}'{/localize}, 10, 'pcp', 10, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Gift{/ts}'{/localize}, 11, 'gift', 11, 0, 1, 1);

ALTER TABLE `civicrm_contribution_soft`
  ADD COLUMN `soft_credit_type_id`  int(10) unsigned COMMENT 'Soft Credit Type ID.Implicit FK to civicrm_option_value where option_group = soft_credit_type.';

INSERT INTO civicrm_contribution_soft(contribution_id, contact_id, amount, currency, soft_credit_type_id)
SELECT id, honor_contact_id, total_amount, currency, honor_type_id
FROM civicrm_contribution
WHERE honor_contact_id IS NOT NULL;

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
   ('honoree_individual', 'Individual,Contact', {localize}'{ts escape="sql"}Honoree Individual{/ts}'{/localize}, 0, 1);

SELECT @uf_group_id_honoree_individual := id from civicrm_uf_group where name = 'honoree_individual';
SELECT @primaryLocation := id FROM civicrm_location_type WHERE is_default = 1;

INSERT INTO `civicrm_uf_field`
      (`uf_group_id`, `field_name`, `is_required`, `is_reserved`, `weight`, `visibility`, `in_selector`, `is_searchable`, `location_type_id`, {localize field='label'}`label`{/localize}, field_type)
VALUES
      (@uf_group_id_honoree_individual, 'prefix_id',  0, 1, 1, 'User and User Admin Only', 0, 1, NULL, {localize}'{ts escape="sql"}Individual Prefix{/ts}'{/localize}, 'Individual'),
      (@uf_group_id_honoree_individual, 'first_name', 0, 1, 2, 'User and User Admin Only', 0, 1, NULL, {localize}'{ts escape="sql"}First Name{/ts}'{/localize},        'Individual'),
      (@uf_group_id_honoree_individual, 'last_name',  0, 1, 3, 'User and User Admin Only', 0, 1, NULL, {localize}'{ts escape="sql"}Last Name{/ts}'{/localize},         'Individual'),
      (@uf_group_id_honoree_individual, 'email',      0, 1, 4, 'User and User Admin Only', 0, 1, @primaryLocation, {localize}'{ts escape="sql"}Email Address{/ts}'{/localize},     'Individual');

UPDATE civicrm_uf_join SET uf_group_id = @uf_group_id_honoree_individual WHERE module = 'soft_credit';

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
SELECT @option_group_id_act_val := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
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
ALTER TABLE  `civicrm_mailing_bounce_type` CHANGE  `name`  `name` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'Type of bounce';
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
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1157, "PL", "Plateau");
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

-- Add "developer" help menu
SELECT @parent_id := `id` FROM `civicrm_navigation` WHERE `name` = 'Help' AND `domain_id` = {$domainID};

INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, NULL, '{ts escape="sql" skip="true"}Developer{/ts}', 'Developer', 'administer CiviCRM', '', @parent_id, '1', NULL, 5 );

SET @devellastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( {$domainID}, 'civicrm/api/explorer', '{ts escape="sql" skip="true"}API Explorer{/ts}','API Explorer', 'administer CiviCRM', '', @devellastID, '1', NULL, 1 ),
( {$domainID}, 'http://wiki.civicrm.org/confluence/display/CRMDOC/Develop', '{ts escape="sql" skip="true"}Developer Docs{/ts}', 'Developer Docs', 'administer CiviCRM', '', @devellastID, '1', NULL, 3 );

-- CRM-14435
ALTER TABLE `civicrm_mail_settings`
  ADD CONSTRAINT `FK_civicrm_mail_settings_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`) ON DELETE CASCADE;

-- CRM-14436
ALTER TABLE `civicrm_mailing`
  ADD COLUMN `hash` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Key for validating requests related to this mailing.',
  ADD INDEX `index_hash` (`hash`);

-- CRM-14300
UPDATE `civicrm_event` SET is_template = 0 WHERE is_template IS NULL;
ALTER TABLE `civicrm_event`
  CHANGE is_template is_template tinyint(4) DEFAULT '0' COMMENT 'whether the event has template';

-- CRM-14493
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES (1085, "61", "Pieria");

-- CRM-14445
ALTER TABLE `civicrm_option_group`
    ADD COLUMN `is_locked` int(1) DEFAULT 0 COMMENT 'A lock to remove the ability to add new options via the UI';

UPDATE `civicrm_option_group` SET is_locked = 1 WHERE name IN ('contribution_status','activity_contacts','advanced_search_options','auto_renew_options','contact_autocomplete_options','batch_status','batch_type','batch_mode','contact_edit_options','contact_reference_options','contact_smart_group_display','contact_view_options','financial_item_status','mapping_type','pcp_status','user_dashboard_options','tag_used_for');

-- CRM-14449
CREATE TABLE IF NOT EXISTS `civicrm_system_log` (
`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: ID.',
`message` VARCHAR(128) NOT NULL COMMENT 'Standardized message',
`context` LONGTEXT NULL COMMENT 'JSON encoded data',
`level` VARCHAR(9)  NOT NULL DEFAULT 'info' COMMENT 'error level per PSR3',
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when event occurred.',
`contact_id` INT(11) NULL DEFAULT NULL COMMENT 'Optional Contact ID that created the log. Not an FK as we keep this regardless',
 hostname VARCHAR(128) NOT NULL COMMENT 'Optional Name of logging host',
PRIMARY KEY (`id`),
INDEX `message` (`message`),
INDEX `contact_id` (`contact_id`),
INDEX `level` (`level`)
)
COMMENT='Table that contains logs of all system events.'
COLLATE='utf8_general_ci';

-- CRM-14473 civicrm_case_type table creation and migration
CREATE TABLE IF NOT EXISTS `civicrm_case_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Autoincremented type id',
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Machine name for Case Type',
  {localize field='title'}title varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Natural language name for Case Type'{/localize},
  {localize field='description'}description varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Description of the Case Type'{/localize},
  `is_active` tinyint(4) DEFAULT NULL COMMENT 'Is this entry active?',
  `is_reserved` tinyint(4) DEFAULT NULL COMMENT 'Is this case type a predefined system type?',
  `weight` int(11) NOT NULL DEFAULT '1' COMMENT 'Ordering of the case types',
  `definition` blob    COMMENT 'xml definition of case type',
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

SELECT @option_group_id_case_type := max(id) from civicrm_option_group where name = 'case_type';

INSERT IGNORE INTO civicrm_case_type
  (id, name, {localize field='title'}title{/localize}, {localize field='description'}description{/localize}, is_active, is_reserved, weight)
  SELECT
    value,
    name,
    {localize field='label'}label{/localize},
    {localize field='description'}description{/localize},
    is_active,
    is_reserved,
    weight
  FROM civicrm_option_value
  WHERE
    option_group_id = @option_group_id_case_type;

-- Remove the special character, earlier used as a separator and reference to civicrm_case_type.id
UPDATE civicrm_case SET case_type_id = replace(case_type_id, 0x01, '');

ALTER TABLE civicrm_case
  MODIFY case_type_id int(10) unsigned COLLATE utf8_unicode_ci NULL COMMENT 'FK to civicrm_case_type.id',
  ADD CONSTRAINT FK_civicrm_case_case_type_id FOREIGN KEY (case_type_id) REFERENCES civicrm_case_type (id) ON DELETE SET NULL;

-- CRM-15343 set the auto increment civicrm_case_type.id start point to max id to avoid conflict in future insertion
SELECT @max_case_type_id := max(id) from civicrm_case_type;
SET @query  = CONCAT("ALTER TABLE civicrm_case_type AUTO_INCREMENT = ", IFNULL(@max_case_type_id,1));
PREPARE alter_case_type_auto_inc FROM @query;
EXECUTE alter_case_type_auto_inc;
DEALLOCATE PREPARE alter_case_type_auto_inc;

DELETE FROM civicrm_option_value WHERE option_group_id = @option_group_id_case_type;

DELETE FROM civicrm_option_group WHERE id = @option_group_id_case_type;

-- CRM-14611
{if $multilingual}
  {foreach from=$locales item=locale}
      ALTER TABLE civicrm_survey ADD title_{$locale} varchar(255);
      UPDATE civicrm_survey SET title_{$locale} = title;

      ALTER TABLE civicrm_survey ADD instructions_{$locale} TEXT;
      UPDATE civicrm_survey SET instructions_{$locale} = instructions;
  {/foreach}

  ALTER TABLE civicrm_survey DROP title;
  ALTER TABLE civicrm_survey DROP instructions;
{/if}

-- CRM-11182 -- Make event confirmation page optional
 ALTER TABLE civicrm_event
   ADD COLUMN is_confirm_enabled tinyint(4) DEFAULT '1';

 UPDATE civicrm_event
   SET is_confirm_enabled = 1
   WHERE is_monetary = 1;

 UPDATE civicrm_event
   SET is_confirm_enabled = 0
   WHERE is_monetary = 0;

-- CRM-11182
ALTER TABLE civicrm_event
  ADD COLUMN dedupe_rule_group_id int(10) unsigned DEFAULT NULL COMMENT 'Rule to use when matching registrations for this event',
  ADD CONSTRAINT `FK_civicrm_event_dedupe_rule_group_id` FOREIGN KEY (`dedupe_rule_group_id`) REFERENCES `civicrm_dedupe_rule_group` (`id`);

-- CRM-9288
SELECT @option_web_id := id  FROM civicrm_option_group WHERE name = 'website_type';

SELECT @website_default := value FROM civicrm_option_value WHERE option_group_id = @option_web_id and is_default = 1;

SELECT @website_work := value FROM civicrm_option_value WHERE option_group_id = @option_web_id and name= 'Work';

UPDATE civicrm_option_value
SET is_default = 1 WHERE option_group_id = @option_web_id and value = IFNULL(@website_default , @website_work);

SELECT @website_default := value FROM civicrm_option_value WHERE option_group_id = @option_web_id and is_default = 1;

ALTER TABLE civicrm_uf_field
  ADD COLUMN `website_type_id` int(10) unsigned DEFAULT NULL COMMENT 'Website Type Id, if required' AFTER phone_type_id,
  ADD INDEX `IX_website_type_id` (`website_type_id`);

UPDATE civicrm_uf_field
SET website_type_id = @website_default,
field_name = 'url' WHERE field_name LIKE 'url%';

SELECT @website_value := max(cast(value as UNSIGNED)) FROM civicrm_option_value WHERE option_group_id = @option_web_id;
SELECT @website_weight := max(weight) FROM civicrm_option_value WHERE option_group_id = @option_web_id;

INSERT INTO civicrm_option_value(option_group_id, {localize field='label'}label{/localize}, name, value, weight)
SELECT @option_web_id, {localize}website{/localize}, website, (@website_value := @website_value + 1), (@website_weight := @website_weight + 1) FROM (
SELECT 'Google+' AS website
    UNION ALL
SELECT 'Instagram' AS website
    UNION ALL
SELECT 'LinkedIn' AS website
    UNION ALL
SELECT 'Pinterest' AS website
    UNION ALL
SELECT 'Tumblr' AS website
    UNION ALL
SELECT 'SnapChat' AS website
    UNION ALL
SELECT 'Vine' AS website
) AS temp
LEFT JOIN civicrm_option_value co ON LOWER(co.name) = LOWER(temp.website)
AND option_group_id = @option_web_id
WHERE co.id IS NULL;

-- CRM-14627 civicrm navigation inconsistent
UPDATE civicrm_navigation
SET civicrm_navigation.url = CONCAT(SUBSTRING(url FROM 1 FOR LOCATE('&', url) - 1), '?', SUBSTRING(url FROM LOCATE('&', url) + 1))
WHERE civicrm_navigation.url LIKE "%&%" AND civicrm_navigation.url NOT LIKE "%?%";

-- CRM-14478 Add a "cleanup" policy for managed entities
ALTER TABLE `civicrm_managed`
ADD COLUMN `cleanup` varchar(32) COMMENT 'Policy on when to cleanup entity (always, never, unused)';

-- CRM-14639

SELECT @option_grant_status := id  FROM civicrm_option_group WHERE name = 'grant_status';
UPDATE civicrm_option_value
SET
{if !$multilingual}
  label =
    CASE
      WHEN lower(name) = 'granted'
      THEN 'Paid'
      WHEN lower(name) = 'approved'
      THEN 'Eligible'
      WHEN lower(name) = 'rejected'
      THEN 'Ineligible'
      ELSE 'Submitted'
    END,
{else}
  {foreach from=$locales item=locale}
    label_{$locale} =
      CASE
        WHEN lower(name) = 'granted'
          THEN 'Paid'
          WHEN lower(name) = 'approved'
          THEN 'Eligible'
          WHEN lower(name) = 'rejected'
          THEN 'Ineligible'
          ELSE 'Submitted'
      END,
  {/foreach}
{/if}
name =
CASE
  WHEN lower(name) = 'granted'
   THEN 'Paid'
  WHEN lower(name) = 'approved'
   THEN 'Eligible'
  WHEN lower(name) = 'rejected'
   THEN 'Ineligible'
  ELSE 'Submitted'
END
WHERE option_group_id = @option_grant_status and LOWER(name) IN ('granted', 'pending', 'approved', 'rejected');

SELECT @grant_value := max(cast(value as UNSIGNED)) FROM civicrm_option_value WHERE option_group_id = @option_grant_status;
SELECT @grant_weight := max(weight) FROM civicrm_option_value WHERE option_group_id = @option_grant_status;

INSERT INTO civicrm_option_value(option_group_id, {localize field='label'}label{/localize}, name, value, weight)
SELECT @option_grant_status, {localize}grantstatus{/localize}, grantstatus, @grant_value := @grant_value + 1, @grant_weight := @grant_weight + 1 FROM (
SELECT 'Submitted' AS grantstatus
    UNION ALL
SELECT 'Approved for Payment' AS grantstatus
    UNION ALL
SELECT 'Eligible' AS grantstatus
    UNION ALL
SELECT 'Awaiting Information' AS grantstatus
    UNION ALL
SELECT 'Withdrawn' AS grantstatus
) AS temp
LEFT JOIN civicrm_option_value co ON LOWER(co.name) = LOWER(temp.grantstatus)
AND option_group_id = @option_grant_status
WHERE co.id IS NULL;

-- Fix trailing single quote in grant status label
{if !$multilingual}
  UPDATE civicrm_option_value v
    INNER JOIN civicrm_option_group g
    ON v.option_group_id = g.id AND g.name = 'grant_status'
    SET label = 'Awaiting Information'
    WHERE v.label = 'Awaiting Information\'' and v.name = 'Awaiting Information';
{else}
  UPDATE civicrm_option_value v
    INNER JOIN civicrm_option_group g
    ON v.option_group_id = g.id AND g.name = 'grant_status'
    SET
    {foreach from=$locales item=locale}
      v.label_{$locale} = CASE
        WHEN v.label_{$locale} = 'Awaiting Information\'' THEN 'Awaiting Information'
        ELSE v.label_{$locale}
      END,
    {/foreach}
    v.name = v.name
    WHERE v.name = 'Awaiting Information';
{/if}

-- CRM-14197 Add contribution_id to civicrm_line_item

ALTER TABLE civicrm_line_item ADD contribution_id INT(10) unsigned COMMENT 'Contribution ID' NULL AFTER entity_id;

-- FK to civicrm_contribution

ALTER TABLE civicrm_line_item
ADD CONSTRAINT `FK_civicrm_contribution_id` FOREIGN KEY (`contribution_id`) REFERENCES civicrm_contribution (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_line_item`
DROP INDEX `UI_line_item_value`,
ADD UNIQUE INDEX `UI_line_item_value` (`entity_table`, `entity_id`, `contribution_id`, `price_field_value_id`, `price_field_id`);

-- update case type menu
UPDATE civicrm_navigation set url = 'civicrm/a/#/caseType' WHERE url LIKE 'civicrm/admin/options/case_type%';

