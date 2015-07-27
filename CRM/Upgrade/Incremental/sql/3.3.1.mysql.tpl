-- CRM-7127
ALTER TABLE civicrm_membership_type
    DROP FOREIGN KEY `FK_civicrm_membership_type_relationship_type_id`,
    DROP INDEX `FK_civicrm_membership_type_relationship_type_id`,
    CHANGE relationship_type_id relationship_type_id VARCHAR( 64 ) NULL DEFAULT NULL,
    CHANGE relationship_direction relationship_direction VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE civicrm_membership_type
    ADD INDEX index_relationship_type_id(relationship_type_id);