{* file to handle db changes in 5.29.alpha1 during upgrade *}

-- The RelationshipVortex is a high-level index/cache for querying relationships.
DROP TABLE IF EXISTS `civicrm_relationship_vtx`;
CREATE TABLE `civicrm_relationship_vtx` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Relationship Vortex ID',
     `relationship_id` int unsigned NOT NULL   COMMENT 'id of the relationship',
     `relationship_type_id` int unsigned NOT NULL   COMMENT 'id of the relationship',
     `orientation` char(3) NOT NULL   COMMENT 'The vortex record is a permutation of the original relationship record. The orientation indicates whether it is forward (a_b) or reverse (b_a) relationship.',
     `near_contact_id` int unsigned NOT NULL   COMMENT 'id of the first contact',
     `relation` varchar(64)    COMMENT 'name for relationship of near_contact to far_contact.',
     `far_contact_id` int unsigned NOT NULL   COMMENT 'id of the second contact',
     PRIMARY KEY (`id`),
     UNIQUE INDEX `UI_relationship`(relationship_id, orientation),
     INDEX `index_nearid_relation`(near_contact_id, relation),
     INDEX `index_relation`(relation),

     CONSTRAINT FK_civicrm_relationship_vtx_relationship_id FOREIGN KEY (`relationship_id`) REFERENCES `civicrm_relationship`(`id`) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_relationship_vtx_relationship_type_id FOREIGN KEY (`relationship_type_id`) REFERENCES `civicrm_relationship_type`(`id`) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_relationship_vtx_near_contact_id FOREIGN KEY (`near_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_relationship_vtx_far_contact_id FOREIGN KEY (`far_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
