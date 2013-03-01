-- CRM-6554
SELECT @domainID := min(id) FROM civicrm_domain;
SELECT @navid := id FROM civicrm_navigation WHERE name='Option Lists';
SELECT @wt := max(weight) FROM civicrm_navigation WHERE parent_id=@navid;
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( @domainID, 'civicrm/admin/options/wordreplacements&reset=1',                                                              '{ts escape="sql"}Word Replacements{/ts}',       'Word Replacements',                         'administer CiviCRM', '',   @navid, '1', NULL, @wt + 1);

-- CRM-6532
UPDATE civicrm_state_province SET name = 'Bahia'     WHERE name = 'Baia';
UPDATE civicrm_state_province SET name = 'Tocantins' WHERE name = 'Tocatins';

-- CRM-6330
SELECT @nav_mt    := id FROM civicrm_navigation WHERE name = 'Manage Tags (Categories)';
SELECT @nav_fmdc  := id FROM civicrm_navigation WHERE name = 'Find and Merge Duplicate Contacts';
SELECT @nav_c     := id FROM civicrm_navigation WHERE name = 'Contacts';
SELECT @nav_c_wt  := max(weight) from civicrm_navigation WHERE parent_id = @nav_c;

DELETE FROM	civicrm_navigation WHERE id = @nav_fmdc;

UPDATE civicrm_navigation SET has_separator = '1' where id = @nav_mt;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/contact/deduperules&reset=1', '{ts escape="sql"}Find and Merge Duplicate Contacts{/ts}','Find and Merge Duplicate Contacts', 'administer dedupe rules,merge duplicate contacts', 'OR', @nav_c, '1', NULL, @nav_c_wt+1 );

--CRM-6565
ALTER TABLE civicrm_activity
ADD INDEX UI_source_record_id(source_record_id),
ADD INDEX index_medium_id (medium_id),
ADD INDEX index_is_current_revision (is_current_revision),
ADD INDEX index_is_deleted (is_deleted);

{if $addActivityTypeIndex}
ALTER TABLE civicrm_activity
ADD INDEX UI_activity_type_id (activity_type_id);
{/if}

-- CRM-6622
SELECT @uf_group_id_summary   := max(id) FROM civicrm_uf_group WHERE name = 'summary_overlay';
UPDATE civicrm_uf_field SET phone_type_id = 1 WHERE uf_group_id = @uf_group_id_summary AND location_type_id = 1 AND field_name = 'phone' AND phone_type_id IS NULL;
UPDATE civicrm_uf_field SET location_type_id = 1, phone_type_id = 2 WHERE uf_group_id = @uf_group_id_summary AND location_type_id = 2 AND field_name = 'phone' AND phone_type_id IS NULL;

-- CRM-6577
ALTER TABLE `civicrm_mapping_field` ADD COLUMN `website_type_id` int(10) unsigned DEFAULT NULL COMMENT 'Which type of website does this site belong';

-- CRM-6631
{include file='../CRM/Upgrade/3.2.1.msg_template/civicrm_msg_template.tpl'}