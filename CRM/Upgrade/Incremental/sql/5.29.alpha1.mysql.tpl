{* file to handle db changes in 5.29.alpha1 during upgrade *}

{* https://github.com/civicrm/civicrm-core/pull/17824 *}
UPDATE civicrm_status_pref SET name = 'checkExtensionsOk' WHERE name = 'extensionsOk';
UPDATE civicrm_status_pref SET name = 'checkExtensionsUpdates' WHERE name = 'extensionUpdates';

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
)  ENGINE=InnoDB;

-- Fix missing resubscribeUrl token. There doesn't seem to be any precedent
-- for doing an upgrade for these, since the last update was in 2009 when
-- the token went missing and it had no upgrade script for it. Also unlike
-- message templates, there doesn't seem to be a way to tell whether it's
-- been changed. Using ts is a bit unreliable if the translation has changed
-- but it would be no worse than now and just end up not updating it.
-- Also, I'm drawing a blank on why the %3 is replaced differently during
-- install than during upgrade, hence the OR clause.
{capture assign=unsubgroup}{ldelim}unsubscribe.group{rdelim}{/capture}
{capture assign=actresub}{ldelim}action.resubscribe{rdelim}{/capture}
{capture assign=actresuburl}{ldelim}action.resubscribeUrl{rdelim}{/capture}
UPDATE civicrm_mailing_component
SET body_text = '{ts escape="sql" 1=$unsubgroup 2=$actresub 3=$actresuburl}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking %3{/ts}'
WHERE component_type  = 'Unsubscribe'
AND (body_text = '{ts escape="sql" 1=$unsubgroup 2=$actresub}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking %3{/ts}'
  OR body_text = '{ts escape="sql" 1=$unsubgroup 2=$actresub}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking {/ts}');
