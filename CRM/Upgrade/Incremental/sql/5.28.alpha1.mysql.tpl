{* file to handle db changes in 5.28.alpha1 during upgrade *}

-- https://github.com/civicrm/civicrm-core/pull/17450
ALTER TABLE `civicrm_activity` CHANGE `activity_date_time` `activity_date_time` datetime NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time this activity is scheduled to occur. Formerly named scheduled_date_time.';
ALTER TABLE `civicrm_activity` CHANGE `created_date` `created_date` timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the activity was created.';

-- https://github.com/civicrm/civicrm-core/pull/17548
ALTER table civicrm_contact_type modify name varchar(64) not null comment 'Internal name of Contact Type (or Subtype).';
