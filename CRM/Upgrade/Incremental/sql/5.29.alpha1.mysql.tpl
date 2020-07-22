{* file to handle db changes in 5.29.alpha1 during upgrade *}

-- The RelationshipCache is a high-level index/cache for querying relationships.
DROP TABLE IF EXISTS `civicrm_relationship_cache`;
CREATE TABLE `civicrm_relationship_cache` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Relationship Cache ID',
     `relationship_id` int unsigned NOT NULL   COMMENT 'id of the relationship (FK to civicrm_relationship.id)',
     `relationship_type_id` int unsigned NOT NULL   COMMENT 'id of the relationship type',
     `orientation` char(3) NOT NULL   COMMENT 'The cache record is a permutation of the original relationship record. The orientation indicates whether it is forward (a_b) or reverse (b_a) relationship.',
     `near_contact_id` int unsigned NOT NULL   COMMENT 'id of the first contact',
     `near_relation` varchar(64)    COMMENT 'name for relationship of near_contact to far_contact.',
     `far_contact_id` int unsigned NOT NULL   COMMENT 'id of the second contact',
     `far_relation` varchar(64)    COMMENT 'name for relationship of far_contact to near_contact.',
     `is_active` tinyint   DEFAULT 1 COMMENT 'is the relationship active ?',
     `start_date` date    COMMENT 'date when the relationship started',
     `end_date` date    COMMENT 'date when the relationship ended',
     PRIMARY KEY (`id`),
     UNIQUE INDEX `UI_relationship`(relationship_id, orientation),
     INDEX `index_nearid_nearrelation`(near_contact_id, near_relation),
     INDEX `index_nearid_farrelation`(near_contact_id, far_relation),
     INDEX `index_near_relation`(near_relation),
     CONSTRAINT FK_civicrm_relationship_cache_relationship_id FOREIGN KEY (`relationship_id`) REFERENCES `civicrm_relationship`(`id`) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_relationship_cache_relationship_type_id FOREIGN KEY (`relationship_type_id`) REFERENCES `civicrm_relationship_type`(`id`) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_relationship_cache_near_contact_id FOREIGN KEY (`near_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_relationship_cache_far_contact_id FOREIGN KEY (`far_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
