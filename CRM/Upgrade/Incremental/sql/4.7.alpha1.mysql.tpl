{* file to handle db changes in 4.7.alpha1 during upgrade *}
-- CRM-16195: Move relative date filters from code to database
INSERT INTO
   `civicrm_option_group` (`name`, `title`, `is_reserved`, `is_active`, `is_locked`)
   VALUES
   ('relative_date_filters'         , '{ts escape="sql"}Relative Date Filters{/ts}'              , 1, 1, 0);

SELECT @option_group_id_date_filter    := max(id) from civicrm_option_group where name = 'relative_date_filters';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
   VALUES
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Month{/ts}', 'previous.month', 'previous.month', NULL, NULL, NULL, 1, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous Week{/ts}', 'previous.week', 'previous.week', NULL, NULL, NULL, 2, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Month{/ts}', 'this.month', 'this.month', NULL, NULL, NULL, 3, NULL, 0, 0, 1, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This Week{/ts}', 'this.week', 'this.week', NULL, NULL, NULL, 4, NULL, 0, 0, 1, NULL, NULL);
