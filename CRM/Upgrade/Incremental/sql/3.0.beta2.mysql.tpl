-- CRM-4979

INSERT INTO civicrm_acl
    (name, deny, entity_table, entity_id, operation, object_table, object_id, acl_table, acl_id, is_active)
VALUES
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete activities', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete in CiviContribute', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete in CiviMail', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete in CiviPledge', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete contacts', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete in CiviEvent', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'delete in CiviMember', NULL, NULL, NULL, 1);
