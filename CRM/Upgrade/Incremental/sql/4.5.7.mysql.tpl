{* file to handle db changes in 4.5.7 during upgrade *}

-- CRM-15931
UPDATE civicrm_mailing_group SET group_type = 'Include' WHERE group_type = 'include';
UPDATE civicrm_mailing_group SET group_type = 'Exclude' WHERE group_type = 'exclude';
UPDATE civicrm_mailing_group SET group_type = 'Base' WHERE group_type = 'base';
