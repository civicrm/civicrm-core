-- CRM-3507: upgrade message templates (if changed)
{include file='../CRM/Upgrade/3.1.beta1.msg_template/civicrm_msg_template.tpl'}

--  CRM-5388
-- we definitely shouldn't fix non-English strings, so skipping the multilingual part
{if !$multilingual}
-- prefix
    UPDATE civicrm_option_value SET label = 'Mr.' , name = 'Mr.'  WHERE label = 'Mr'  AND name = 'Mr';
    UPDATE civicrm_option_value SET label = 'Ms.' , name = 'Ms.'  WHERE label = 'Ms'  AND name = 'Ms';
    UPDATE civicrm_option_value SET label = 'Mrs.', name = 'Mrs.' WHERE label = 'Mrs' AND name = 'Mrs';
    UPDATE civicrm_option_value SET label = 'Dr.',  name = 'Dr.'  WHERE label = 'Dr'  AND name = 'Dr';

-- suffix
    UPDATE civicrm_option_value SET label = 'Jr.',  name = 'Jr.'  WHERE label = 'Jr'  AND name = 'Jr';
    UPDATE civicrm_option_value SET label = 'Sr.',  name = 'Sr.'  WHERE label = 'Sr'  AND name = 'Sr';
{/if}

--  CRM-5435
ALTER TABLE `civicrm_contribution_soft` 
    ADD CONSTRAINT `FK_civicrm_contribution_soft_pcp_id` FOREIGN KEY (`pcp_id`) REFERENCES `civicrm_pcp` (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_contribution_soft` 
    CHANGE `pcp_id` `pcp_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_pcp.id';

ALTER TABLE `civicrm_pcp_block`  
    ADD CONSTRAINT `FK_civicrm_pcp_block_supporter_profile_id` FOREIGN KEY (`supporter_profile_id`) REFERENCES `civicrm_uf_group` (`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_pcp_block`
    CHANGE `supporter_profile_id` `supporter_profile_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_uf_group.id. Does Personal Campaign Page require manual activation by administrator? (is inactive by default after setup)?';

-- CRM-5322

  SELECT @option_group_id_sfe := max(id) from civicrm_option_group where name = 'safe_file_extension';
  SELECT @max_val             := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_sfe;
  SELECT @max_wt              := max(weight) from civicrm_option_value where option_group_id= @option_group_id_sfe;

  INSERT INTO civicrm_option_value
    (option_group_id,      {localize field='label'}label{/localize}, value,                           filter, weight) VALUES
    (@option_group_id_sfe, {localize}'docx'{/localize},              (SELECT @max_val := @max_val+1), 0,      (SELECT @max_wt := @max_wt+1)),
    (@option_group_id_sfe, {localize}'xlsx'{/localize},              (SELECT @max_val := @max_val+1), 0,      (SELECT @max_wt := @max_wt+1));

--
-- handle schema changes from v3.0.3 once again, CRM-5463
--
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

--v3.0.3 changes end.

