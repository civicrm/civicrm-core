{* file to handle db changes in 4.6.alpha7 during upgrade *}

-- location_type_id should have default NULL, not invalid id 0
ALTER TABLE civicrm_mailing CHANGE `location_type_id` `location_type_id` int(10) unsigned DEFAULT NULL COMMENT 'With email_selection_method, determines which email address to use';
