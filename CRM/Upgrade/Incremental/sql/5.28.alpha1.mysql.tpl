{* file to handle db changes in 5.28.alpha1 during upgrade *}

-- https://github.com/civicrm/civicrm-core/pull/17579
ALTER TABLE `civicrm_navigation` CHANGE `has_separator`
`has_separator` tinyint   DEFAULT 0 COMMENT 'Place a separator either before or after this menu item.';

-- https://github.com/civicrm/civicrm-core/pull/17450
ALTER TABLE `civicrm_activity` CHANGE `activity_date_time` `activity_date_time` datetime NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time this activity is scheduled to occur. Formerly named scheduled_date_time.';
ALTER TABLE `civicrm_activity` CHANGE `created_date` `created_date` timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the activity was created.';

-- https://github.com/civicrm/civicrm-core/pull/17548
ALTER TABLE civicrm_contact_type CHANGE name  name varchar(64) not null comment 'Internal name of Contact Type (or Subtype).';
ALTER TABLE civicrm_contact_type CHANGE is_active is_active tinyint DEFAULT 1  COMMENT 'Is this entry active?';
ALTER TABLE civicrm_contact_type CHANGE is_reserved is_reserved tinyint DEFAULT 0  COMMENT 'Is this contact type a predefined system type';
UPDATE civicrm_contact_type SET is_active = 1 WHERE is_active IS NULL;
UPDATE civicrm_contact_type SET is_reserved = 0 WHERE is_reserved IS NULL;
