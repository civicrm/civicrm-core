 -- CRM-5333
 -- Delete duplicate records in target and assignment exists if any
 
 DELETE cat.* FROM civicrm_activity_target cat 
              INNER JOIN ( SELECT id, activity_id, target_contact_id 
                           FROM civicrm_activity_target 
                           GROUP BY activity_id, target_contact_id HAVING count(*) > 1 ) dup_cat 
                      ON ( cat.activity_id = dup_cat.activity_id 
                           AND cat.target_contact_id = dup_cat.target_contact_id 
                           AND cat.id <> dup_cat.id );

 DELETE caa.* FROM civicrm_activity_assignment caa 
              INNER JOIN ( SELECT id, activity_id, assignee_contact_id 
                           FROM civicrm_activity_assignment 
                           GROUP BY activity_id, assignee_contact_id HAVING count(*) > 1 ) dup_caa 
                      ON ( caa.activity_id = dup_caa.activity_id 
                           AND caa.assignee_contact_id = dup_caa.assignee_contact_id 
                           AND caa.id <> dup_caa.id );

 -- Drop unique indexes of activity_target and activity_assignment

  ALTER TABLE  civicrm_activity_assignment 
  DROP INDEX `UI_activity_assignee_contact_id` ,
  ADD UNIQUE INDEX `UI_activity_assignee_contact_id` (`assignee_contact_id`,`activity_id`);

  ALTER TABLE  civicrm_activity_target 
  DROP INDEX `UI_activity_target_contact_id` ,
  ADD UNIQUE INDEX `UI_activity_target_contact_id` (`target_contact_id`,`activity_id`);


-- CRM-5437
UPDATE civicrm_participant_status_type SET class = 'Pending' WHERE class NOT IN ('Positive', 'Pending', 'Waiting', 'Negative');

-- CRM-5451
ALTER TABLE `civicrm_custom_group`
DROP FOREIGN KEY `FK_civicrm_custom_group_created_id`;

ALTER TABLE `civicrm_custom_group`
ADD CONSTRAINT `FK_civicrm_custom_group_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_event`
DROP FOREIGN KEY `FK_civicrm_event_created_id`;

ALTER TABLE `civicrm_event`
ADD CONSTRAINT `FK_civicrm_event_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_contribution_page`
DROP FOREIGN KEY `FK_civicrm_contribution_page_created_id`;

ALTER TABLE `civicrm_contribution_page`
ADD CONSTRAINT `FK_civicrm_contribution_page_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_uf_group`
DROP FOREIGN KEY `FK_civicrm_uf_group_created_id`;

ALTER TABLE `civicrm_uf_group`
ADD CONSTRAINT `FK_civicrm_uf_group_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

-- CRM-5471
UPDATE civicrm_mailing_bounce_pattern
   SET pattern = 'delivery to the following recipient(s)? failed'
 WHERE pattern = 'delivery to the following recipients failed';

UPDATE civicrm_navigation SET permission ='access CiviCRM', permission_operator ='' WHERE civicrm_navigation.name= 'Manage Groups';