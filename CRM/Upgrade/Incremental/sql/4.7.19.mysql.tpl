-- CRM-20313 Add index to civicrm_activity.status_id
ALTER TABLE `civicrm_activity` ADD INDEX UI_status_id (`status_id`);
