{* file to handle db changes in 4.7.19 during upgrade *}

-- CRM-19751
INSERT INTO `civicrm_option_group` (`name`, `title`, `data_type`, `is_reserved`, `is_active`, `is_locked`)
VALUES ('email_on_hold', 'Email On Hold Options', NULL, 1, 1, 1);

SELECT @option_group_id_email_on_hold  := max(id) from civicrm_option_group where name = 'email_on_hold';

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
VALUES
   (@option_group_id_email_on_hold, 'No', '0', 'on_hold_no', NULL, NULL, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_email_on_hold, 'On Hold Bounce', '1', 'on_hold_bounce', NULL, NULL, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_email_on_hold, 'On Hold Opt Out', '2', 'on_hold_opt_out', NULL, NULL, NULL, 3, NULL, 0, 1, 1, NULL, NULL, NULL);
