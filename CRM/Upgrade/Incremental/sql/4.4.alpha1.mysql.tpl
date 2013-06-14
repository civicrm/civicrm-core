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

{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE civicrm_option_value SET label_{$locale} ='Outbound SMS' WHERE name = 'SMS' and option_group_id = @option_group_id_activity_type;
  {/foreach}
{else}
  UPDATE civicrm_option_value SET label ='Outbound SMS' WHERE name = 'SMS' and option_group_id = @option_group_id_activity_type;
{/if}

-- CRM-12689
ALTER TABLE civicrm_action_schedule
  ADD COLUMN limit_to tinyint(4) DEFAULT '1' COMMENT 'Is this the recipient criteria limited to OR in addition to?'  AFTER recipient;

-- CRM-12653
SELECT @uf_group_contribution_batch_entry     := max(id) FROM civicrm_uf_group WHERE name = 'contribution_batch_entry';
SELECT @uf_group_membership_batch_entry       := max(id) FROM civicrm_uf_group WHERE name = 'membership_batch_entry';

INSERT INTO civicrm_uf_field
       ( uf_group_id, field_name, is_required, is_reserved, weight, visibility, in_selector, is_searchable, location_type_id, {localize field='label'}label{/localize}, field_type, help_post, phone_type_id )
VALUES
      ( @uf_group_contribution_batch_entry, 'soft_credit', 0, 0, 10, 'User and User Admin Only', 0, 0, NULL, {localize}'Soft Credit'{/localize}, 'Contribution', NULL, NULL ),
      ( @uf_group_membership_batch_entry, 'soft_credit', 0, 0, 13, 'User and User Admin Only', 0, 0, NULL, {localize}'Soft Credit'{/localize}, 'Membership', NULL, NULL );

-- CRM-12809
ALTER TABLE `civicrm_custom_group`
  ADD COLUMN `is_reserved` tinyint(4) DEFAULT '0' COMMENT 'Is this a reserved Custom Group?';
