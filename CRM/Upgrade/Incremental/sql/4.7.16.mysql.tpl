{* file to handle db changes in 4.7.16 during upgrade *}

-- CRM-19723 add icons
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
UPDATE civicrm_option_value SET icon = 'fa-slideshare' WHERE option_group_id = @option_group_id_act AND name = 'Meeting';
UPDATE civicrm_option_value SET icon = 'fa-phone' WHERE option_group_id = @option_group_id_act AND name = 'Phone Call';
UPDATE civicrm_option_value SET icon = 'fa-envelope-o' WHERE option_group_id = @option_group_id_act AND name = 'Email';
UPDATE civicrm_option_value SET icon = 'fa-mobile' WHERE option_group_id = @option_group_id_act AND name = 'SMS';
UPDATE civicrm_option_value SET icon = 'fa-file-pdf-o' WHERE option_group_id = @option_group_id_act AND name = 'Print PDF Letter';
UPDATE civicrm_option_value SET icon = 'fa-folder-open-o' WHERE option_group_id = @option_group_id_act AND name = 'Open Case';
UPDATE civicrm_option_value SET icon = 'fa-share-square-o' WHERE option_group_id = @option_group_id_act AND name = 'Follow up';
UPDATE civicrm_option_value SET icon = 'fa-random' WHERE option_group_id = @option_group_id_act AND name = 'Change Case Type';
UPDATE civicrm_option_value SET icon = 'fa-pencil-square-o' WHERE option_group_id = @option_group_id_act AND name = 'Change Case Status';
UPDATE civicrm_option_value SET icon = 'fa-calendar' WHERE option_group_id = @option_group_id_act AND name = 'Change Case Start Date';
UPDATE civicrm_option_value SET icon = 'fa-user-plus' WHERE option_group_id = @option_group_id_act AND name = 'Assign Case Role';
UPDATE civicrm_option_value SET icon = 'fa-user-times' WHERE option_group_id = @option_group_id_act AND name = 'Remove Case Role';
UPDATE civicrm_option_value SET icon = 'fa-file-pdf-o' WHERE option_group_id = @option_group_id_act AND name = 'Print PDF Letter';
UPDATE civicrm_option_value SET icon = 'fa-compress' WHERE option_group_id = @option_group_id_act AND name = 'Merge Case';
UPDATE civicrm_option_value SET icon = 'fa-user-circle-o' WHERE option_group_id = @option_group_id_act AND name = 'Reassigned Case';
UPDATE civicrm_option_value SET icon = 'fa-link' WHERE option_group_id = @option_group_id_act AND name = 'Link Cases';
UPDATE civicrm_option_value SET icon = 'fa-tags' WHERE option_group_id = @option_group_id_act AND name = 'Change Case Tags';
UPDATE civicrm_option_value SET icon = 'fa-users' WHERE option_group_id = @option_group_id_act AND name = 'Add Client To Case';

-- CRM-17663 repair null dashlet names
UPDATE `civicrm_dashboard`
  SET name = CONCAT('report/', SUBSTRING_INDEX(SUBSTRING_INDEX(url, '?', 1), '/', -1))
  WHERE name IS NULL AND url LIKE "civicrm/report/instance/%";

