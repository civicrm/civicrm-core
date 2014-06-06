ALTER TABLE `civicrm_action_schedule`
  ADD COLUMN `from_name` varchar(255) AFTER `absolute_date`;

ALTER TABLE `civicrm_action_schedule`
  ADD COLUMN `from_email` varchar(255) AFTER `from_name`;
  
