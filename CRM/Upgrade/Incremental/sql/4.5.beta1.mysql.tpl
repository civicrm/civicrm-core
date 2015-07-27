{* file to handle db changes in 4.5.beta1 during upgrade *}
-- CRM-14807
ALTER TABLE `civicrm_action_schedule`
  ADD COLUMN `from_name` varchar(255) AFTER `absolute_date`;
ALTER TABLE `civicrm_action_schedule`
  ADD COLUMN `from_email` varchar(255) AFTER `from_name`;
