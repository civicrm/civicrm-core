-- http://forum.civicrm.org/?topic=12481
UPDATE civicrm_state_province SET name = 'Guizhou' WHERE name = 'Gulzhou';

-- CRM-5824
{if $multilingual}
    UPDATE civicrm_option_value ov 
INNER JOIN civicrm_option_group og ON ( og.id = ov.option_group_id )
       SET ov.name = ov.label_{$config->lcMessages}
     WHERE og.name = 'case_type' 
       AND ov.name IS NULL;
{else}
    UPDATE civicrm_option_value ov 
INNER JOIN civicrm_option_group og ON ( og.id = ov.option_group_id )
       SET ov.name = ov.label
     WHERE og.name = 'case_type' 
       AND ov.name IS NULL;
{/if}

-- CRM-6008
{if $multilingual}
INSERT INTO civicrm_membership_status 
    ({localize field='name'}`name`{/localize}, `start_event`, `end_event`, `is_current_member`, `is_admin`, `weight`, `is_default`, `is_active`, `is_reserved`)
    (SELECT {localize}'Pending'{/localize}, 'join_date', 'join_date', 0, 0, 5, 0, 1, 1 FROM dual
     WHERE NOT EXISTS (SELECT * FROM civicrm_membership_status WHERE `name_{$config->lcMessages}` = 'Pending'));
 
 UPDATE `civicrm_membership_status` SET `is_reserved` = 1 WHERE `name_{$config->lcMessages}` = 'Pending';
{else}
 INSERT INTO civicrm_membership_status 
   (`name`, `start_event`, `end_event`, `is_current_member`, `is_admin`, `weight`, `is_default`, `is_active`, `is_reserved`)
    (SELECT 'Pending', 'join_date', 'join_date', 0, 0, 5, 0, 1, 1 FROM dual
     WHERE NOT EXISTS (SELECT * FROM civicrm_membership_status WHERE name = 'Pending'));
  UPDATE `civicrm_membership_status` SET `is_reserved` = 1 WHERE `name` = 'Pending';
{/if}