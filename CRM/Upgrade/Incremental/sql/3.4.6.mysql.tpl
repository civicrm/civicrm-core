-- CRM-8483
{include file='../CRM/Upgrade/3.4.6.msg_template/civicrm_msg_template.tpl'}

-- CRM-8619

SELECT @option_group_id_languages := MAX( id ) FROM civicrm_option_group WHERE name = 'languages';

DELETE FROM civicrm_option_value WHERE option_group_id = @option_group_id_languages AND name = 'de_CH';
DELETE FROM civicrm_option_value WHERE option_group_id = @option_group_id_languages AND name = 'es_PR';

SELECT @languages_max_weight := MAX( weight ) FROM civicrm_option_value WHERE option_group_id = @option_group_id_languages;

INSERT INTO civicrm_option_value
  (option_group_id, is_default, is_active, name, value, {localize field='label'}label{/localize}, weight)
VALUES
(@option_group_id_languages, 0, 1, 'de_CH', 'de', {localize}'{ts escape="sql"}German (Swiss){/ts}'{/localize},                   @weight := @languages_max_weight + 1),
(@option_group_id_languages, 0, 1, 'es_PR', 'es', {localize}'{ts escape="sql"}Spanish; Castilian (Puerto Rico){/ts}'{/localize}, @weight := @languages_max_weight + 2);

-- CRM-8653
UPDATE civicrm_dashboard SET url = 'civicrm/report/instance/3&reset=1&section=2&snippet=4&context=dashlet' WHERE url = 'civicrm/report/instance/3&reset=1&section=2&snippet=4';

-- CRM-8654
ALTER TABLE `civicrm_dashboard_contact` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'dashlet content';

-- CRM-8664
SELECT @ogrID           := max(id) from civicrm_option_group where name = 'report_template';
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
SELECT @max_weight      := MAX(ROUND(weight)) from civicrm_option_value WHERE option_group_id = @ogrID;
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight,{localize field='description'}description{/localize}, is_optgroup,is_reserved, is_active, component_id, visibility_id ) 
VALUES
    (@ogrID  , {localize}'{ts escape="sql"}Contribution History By Relationship Report{/ts}'{/localize}, 'contribute/history', 'CRM_Report_Form_Contribute_History', NULL, 0, 0,  @max_weight+1, {localize}'{ts escape="sql"}List contact\'s donation history, grouped by year, along with contributions attributed to any of the contact\'s related contacts.{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL);

-- CRM-8728
SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
{if $bulkEmailActivityType}
  -- make sure Bulk Email is active and resereved
  UPDATE civicrm_option_value SET is_reserved = 1, is_active = 1 WHERE option_group_id=@option_group_id_activity_type AND name='Bulk Email';
{else}
  -- insert activity type Bulk Email
  SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
  SELECT @max_wt  := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;
  INSERT INTO civicrm_option_value
        (option_group_id, {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, value, name, weight, filter, is_reserved, component_id)
  VALUES
    (@option_group_id_activity_type, {localize}'Bulk Email'{/localize}, {localize}'Bulk Email Sent.'{/localize}, (@max_val+1), 'Bulk Email', (SELECT @max_wt := @max_wt+1), 1, 1, NULL);
{/if}

-- CRM-8859
ALTER TABLE `civicrm_prevnext_cache`
  ADD INDEX index_all ( cacheKey, entity_id1, entity_id2, entity_table );

-- CRM-8483

ALTER TABLE `civicrm_price_set`
   ADD `contribution_type_id` int(10) unsigned default NULL COMMENT 'Conditional foreign key to civicrm_contribution_type.id.',
   ADD CONSTRAINT `FK_civicrm_price_set_contribution_type_id` FOREIGN KEY (`contribution_type_id`) REFERENCES `civicrm_contribution_type` (`id`) ON DELETE SET NULL;
  
ALTER TABLE `civicrm_price_field_value`
   ADD `membership_type_id` int(10) unsigned default NULL COMMENT 'Conditional foreign key to civicrm_membership_type.id.',
   ADD CONSTRAINT `FK_civicrm_price_field_value_membership_type_id` FOREIGN KEY (`membership_type_id`) REFERENCES `civicrm_membership_type` (`id`) ON DELETE SET NULL;

SELECT @customizeID      := MAX(id) FROM civicrm_navigation where name = 'Memberships';
SELECT @extensionsWeight := MAX(weight)+1 FROM civicrm_navigation where parent_id = @customizeID;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES            
    ( {$domainID}, 'civicrm/admin/price&reset=1&action=add', '{ts escape="sql" skip="true"}New Price Set{/ts}', 'New Price Set', 'access CiviMember,administer CiviCRM', '', @customizeID, '1', NULL, @extensionsWeight ),
    ( {$domainID}, 'civicrm/admin/price&reset=1',            '{ts escape="sql" skip="true"}Manage Price Sets{/ts}', 'Manage Price Sets', 'access CiviMember,administer CiviCRM', '', @customizeID, '1', NULL, @extensionsWeight+1 );

SELECT @customizeID      := MAX(id) FROM civicrm_navigation where name = 'CiviMember';
SELECT @extensionsWeight := MAX(weight)+1 FROM civicrm_navigation where parent_id = @customizeID;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES            
    ( {$domainID}, 'civicrm/admin/price&reset=1&action=add', '{ts escape="sql" skip="true"}New Price Set{/ts}', 'New Price Set', 'access CiviMember,administer CiviCRM', '', @customizeID, '1', NULL, @extensionsWeight ),
    ( {$domainID}, 'civicrm/admin/price&reset=1',            '{ts escape="sql" skip="true"}Manage Price Sets{/ts}', 'Manage Price Sets', 'access CiviMember,administer CiviCRM', '', @customizeID, '1', NULL, @extensionsWeight+1 );
   

-- CRM-8626
UPDATE civicrm_payment_processor_type
SET url_recur_default      = 'https://checkout.google.com/',
    url_recur_test_default = 'https://sandbox.google.com/checkout/',
    is_recur               = 1
WHERE name = 'Google_Checkout';

UPDATE  civicrm_payment_processor
   SET  is_recur  = 1,
        url_recur = 'https://checkout.google.com/'
 WHERE  payment_processor_type = 'Google_Checkout' AND is_test = 0;

UPDATE  civicrm_payment_processor
   SET  is_recur  = 1,
        url_recur = 'https://sandbox.google.com/checkout/'
 WHERE  payment_processor_type = 'Google_Checkout' AND is_test = 1;
