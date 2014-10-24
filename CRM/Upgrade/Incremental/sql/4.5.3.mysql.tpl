{* file to handle db changes in 4.5.3 during upgrade *}

-- CRM-15500 fix
ALTER TABLE `civicrm_action_schedule` CHANGE `is_repeat` `is_repeat` TINYINT( 4 ) NULL DEFAULT NULL;
