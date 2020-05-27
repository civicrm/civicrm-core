{include file='../CRM/Upgrade/4.4.alpha1.msg_template/civicrm_msg_template.tpl'}

-- CRM-12357
SELECT @option_group_id_cvOpt := max(id) FROM civicrm_option_group WHERE name = 'contact_view_options';
SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op  WHERE op.option_group_id  = @option_group_id_cvOpt;
SELECT @max_wt := MAX(ROUND(val.weight)) FROM civicrm_option_value val WHERE val.option_group_id = @option_group_id_cvOpt;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_cvOpt, {localize}'{ts escape="sql"}Mailings{/ts}'{/localize}, @max_val+1, 'CiviMail', NULL, 0, NULL,  @max_wt+1, 0, 0, 1, NULL, NULL);

INSERT INTO civicrm_setting
  (domain_id, contact_id, is_domain, group_name, name, value)
VALUES
  ({$domainID}, NULL, 1, 'Mailing Preferences', 'write_activity_record', '{serialize}1{/serialize}');

-- CRM-12580
ALTER TABLE civicrm_contact ADD  INDEX index_is_deleted_sort_name(is_deleted, sort_name, id);
ALTER TABLE civicrm_contact DROP INDEX index_is_deleted;

-- CRM-12495
DROP TABLE IF EXISTS `civicrm_task_status`;
DROP TABLE IF EXISTS `civicrm_task`;
DROP TABLE IF EXISTS `civicrm_project`;

-- CRM-12425
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Spam';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern)
VALUES (@bounceTypeID, 'X-HmXmrOriginalRecipient');

-- CRM-12716
UPDATE civicrm_custom_field SET text_length = NULL WHERE html_type = 'TextArea' AND text_length = 255;

-- CRM-12288

SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;

INSERT INTO civicrm_option_value
   (option_group_id, {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, value, name, weight, filter, component_id)
VALUES
   (@option_group_id_activity_type, {localize}'Inbound SMS'{/localize},{localize}'Inbound SMS'{/localize}, (SELECT @max_val := @max_val+1), 'Inbound SMS', (SELECT @max_wt := @max_wt+1), 1, NULL),
   (@option_group_id_activity_type, {localize}'SMS delivery'{/localize},{localize}'SMS delivery'{/localize}, (SELECT @max_val := @max_val+1), 'SMS delivery', (SELECT @max_wt := @max_wt+1), 1, NULL);

-- CRM-13015 replaced if $multilingual w/ localize method
UPDATE `civicrm_option_value` SET {localize field="label"}label = '{ts escape="sql"}Outbound SMS{/ts}'{/localize}
  WHERE name = 'SMS' and option_group_id = @option_group_id_activity_type;

-- CRM-12689
ALTER TABLE civicrm_action_schedule
  ADD COLUMN limit_to tinyint(4) DEFAULT '1' COMMENT 'Is this the recipient criteria limited to OR in addition to?'  AFTER recipient;

-- CRM-12653
SELECT @uf_group_contribution_batch_entry     := max(id) FROM civicrm_uf_group WHERE name = 'contribution_batch_entry';
SELECT @uf_group_membership_batch_entry       := max(id) FROM civicrm_uf_group WHERE name = 'membership_batch_entry';

INSERT INTO civicrm_uf_field
       ( uf_group_id, field_name, is_required, is_reserved, weight, visibility, in_selector, is_searchable, location_type_id, {localize field='label'}label{/localize}, field_type)
VALUES
      ( @uf_group_contribution_batch_entry, 'soft_credit', 0, 0, 10, 'User and User Admin Only', 0, 0, NULL, {localize}'Soft Credit'{/localize}, 'Contribution'),
      ( @uf_group_membership_batch_entry, 'soft_credit', 0, 0, 13, 'User and User Admin Only', 0, 0, NULL, {localize}'Soft Credit'{/localize}, 'Membership');

-- CRM-12809
ALTER TABLE `civicrm_custom_group`
  ADD COLUMN `is_reserved` tinyint(4) DEFAULT '0' COMMENT 'Is this a reserved Custom Group?';

--CRM-12986 fix event_id & contact_id to NOT NULL fields on participant table
SET foreign_key_checks = 0;
ALTER TABLE `civicrm_participant`
  CHANGE COLUMN `event_id` `event_id` INT(10) UNSIGNED NOT NULL,
  CHANGE COLUMN `contact_id` `contact_id` INT(10) UNSIGNED NOT NULL;
SET foreign_key_checks = 1;

-- CRM-12964 civicrm_print_label table creation
CREATE TABLE IF NOT EXISTS `civicrm_print_label` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'User title for for this label layout',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'variable name/programmatic handle for this field.',
  `description` text COLLATE utf8_unicode_ci COMMENT 'Description of this label layout',
  `label_format_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'This refers to name column of civicrm_option_value row in name_badge option group',
  `label_type_id` int(10) unsigned DEFAULT NULL COMMENT 'Implicit FK to civicrm_option_value row in NEW label_type option group',
  `data` longtext COLLATE utf8_unicode_ci COMMENT 'contains json encode configurations options',
  `is_default` tinyint(4) DEFAULT '1' COMMENT 'Is this default?',
  `is_active` tinyint(4) DEFAULT '1' COMMENT 'Is this option active?',
  `is_reserved` tinyint(4) DEFAULT '1' COMMENT 'Is this reserved label?',
  `created_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_contact, who created this label layout',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_print_label_created_id` (`created_id`),
  CONSTRAINT `FK_civicrm_print_label_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- CRM-12964 adding meta-data
INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`)
VALUES
   ('label_type', {localize}'{ts escape="sql"}Label Type{/ts}'{/localize}, 1, 1),
   ('name_badge', {localize}'{ts escape="sql"}Name Badge Format{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_label_type := max(id) from civicrm_option_group where name = 'label_type';
SELECT @option_group_id_name_badge := max(id) from civicrm_option_group where name = 'name_badge';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
 (@option_group_id_label_type, {localize}'{ts escape="sql"}Event Badge{/ts}'{/localize}, 1, 'Event Badge', NULL, 0, NULL, 1, 0, 0, 1, NULL, NULL),
 (@option_group_id_name_badge, {localize}'{ts escape="sql"}Avery 5395{/ts}'{/localize}, '{literal}{"name":"Avery 5395","paper-size":"a4","metric":"mm","lMargin":13.5,"tMargin":3,"NX":2,"NY":4,"SpaceX":15,"SpaceY":8.5,"width":85.7,"height":59.2,"font-size":12,"orientation":"portrait","font-name":"helvetica","font-style":"","lPadding":0,"tPadding":0}{/literal}', 'Avery 5395', NULL, 0, NULL, 1, 0, 0, 1, NULL, NULL);

-- CRM-12964 adding navigation
UPDATE civicrm_navigation
   SET url  = 'civicrm/admin/badgelayout&reset=1',
       name = 'Event Name Badge Layouts',
       label= '{ts escape="sql" skip="true"}Event Name Badge Layouts{/ts}'
 WHERE name = 'Event Badge Formats';

--CRM-12539 change 'Greater London' to 'London'
UPDATE `civicrm_state_province` SET `name` = 'London' WHERE `name` = 'Greater London';

UPDATE `civicrm_premiums` SET {localize field="premiums_nothankyou_label"}premiums_nothankyou_label = '{ts escape="sql"}No thank-you{/ts}'{/localize};

-- CRM-13015 Change address option labels from Additional Address to Supplemental Address
SELECT @option_group_id_addroptions := max(id) from civicrm_option_group where name = 'address_options';

UPDATE civicrm_option_value
  SET {localize field="label"}label = '{ts escape="sql"}Supplemental Address 1{/ts}'{/localize}
  WHERE name = 'supplemental_address_1' AND option_group_id = @option_group_id_addroptions;

UPDATE civicrm_option_value
  SET {localize field="label"}label = '{ts escape="sql"}Supplemental Address 2{/ts}'{/localize}
  WHERE name = 'supplemental_address_2' AND option_group_id = @option_group_id_addroptions;

-- CRM-12717
UPDATE `civicrm_navigation` SET label = '{ts escape="sql"}Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.){/ts}', name = 'Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.)'
WHERE url = 'civicrm/admin/setting/misc&reset=1';

-- CRM-13112
ALTER TABLE civicrm_survey
  ADD is_share TINYINT( 4 ) NULL DEFAULT '1' COMMENT 'Can people share the petition through social media?';

-- CRM-12439
{if $multilingual}
  ALTER TABLE `civicrm_uf_group`
    ADD `description` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Optional verbose description of the profile.' AFTER `group_type`;
{else}
  ALTER TABLE `civicrm_uf_group`
    ADD `description` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Optional verbose description of the profile.' AFTER `title`;
{/if}

--CRM-13142
UPDATE
  civicrm_uf_field uf
  INNER JOIN
  civicrm_uf_group ug ON uf.uf_group_id = ug.id AND ug.is_reserved = 1 AND name = 'membership_batch_entry'
SET uf.is_reserved = 0
WHERE uf.field_name IN ('join_date', 'membership_start_date', 'membership_end_date');

--CRM-13155 - Add searching for recurring contribution data to search has been successfully created.
ALTER TABLE `civicrm_contribution_recur`
 CHANGE COLUMN `next_sched_contribution` `next_sched_contribution_date` DATETIME NULL DEFAULT NULL COMMENT 'At Groundspring this was used by the cron job which triggered payments. If we\'re not doing that but we know about payments, it might still be useful to store for display to org andor contributors.' AFTER `cycle_day`;

