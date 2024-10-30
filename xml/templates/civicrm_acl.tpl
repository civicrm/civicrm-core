-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--
-- sample acl entries

-- Create ACL to edit and view contacts in all groups
INSERT INTO civicrm_acl (name, deny, entity_table, entity_id, operation, object_table, object_id, acl_table, acl_id, is_active, priority)
VALUES
('Edit All Contacts', 0, 'civicrm_acl_role', 1, 'Edit', 'civicrm_group', 0, NULL, NULL, 1, 1);

-- Create default Groups for User Permissioning
INSERT INTO civicrm_group (`id`, `name`, `title`, `frontend_title`, `description`, `frontend_description`, `source`, `saved_search_id`, `is_active`, `visibility`, `group_type`)
VALUES (1, 'Administrators', '{ts escape="sql"}Administrators{/ts}', '{ts escape="sql"}Administrators{/ts}', '{ts escape="sql"}Contacts in this group are assigned Administrator role permissions.{/ts}', '', NULL, NULL, 1, 'User and User Admin Only', '1');

-- Assign above Group (entity) to the Administrator Role
INSERT INTO civicrm_acl_entity_role
    (`acl_role_id`, `entity_table`, `entity_id`, `is_active`)
VALUES
    (1, 'civicrm_group', 1, 1);
