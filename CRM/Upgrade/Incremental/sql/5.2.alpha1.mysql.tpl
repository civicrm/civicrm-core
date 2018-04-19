{* file to handle db changes in 5.2.alpha1 during upgrade *}

-- CRM-21712 Insert aditional relative date filters for Fiscal Year
SELECT @option_group_id_date_filter    := max(id) from civicrm_option_group where name = 'relative_date_filters';
INSERT INTO
  `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`, `icon`, `color`)
VALUES
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}This and previous 2 fiscal years{/ts}'{/localize}, 'this_2.fiscal_year', 'Within the last 2 fiscal years', NULL, 0, 0, 7, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}This and previous fiscal year{/ts}'{/localize}, 'this_1.fiscal_year', 'This and previous fiscal year', NULL, 0, 0, 6, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 fiscal years{/ts}'{/localize}, 'previous_2.fiscal_year', 'Previous 2 fiscal years', NULL, 0, 0, 61, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 3 fiscal years{/ts}'{/localize}, 'previous_3.fiscal_year', 'Previous 3 fiscal years', NULL, 0, 0, 62, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}Year prior to previous fiscal year{/ts}'{/localize}, 'previous_before.fiscal_year', 'Fiscal Year prior to previous Fiscal year', NULL, 0, 0, 68, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}Current fiscal year to-date{/ts}'{/localize}, 'current.fiscal_year', 'Current fiscal year to-date', NULL, 0, 0, 38, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of previous fiscal year{/ts}'{/localize}, 'earlier.fiscal_year', 'To end of previous fiscal year', NULL, 0, 0, 44, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}From start of current fiscal year{/ts}'{/localize}, 'greater.fiscal_year', 'From start of current fiscal year', NULL, 0, 0, 50, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}To end of current fiscal year{/ts}'{/localize}, 'less.fiscal_year', 'To end of current fiscal year', NULL, 0, 0, 55, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL),
  (@option_group_id_date_filter, {localize}'{ts escape="sql"}From end of previous fiscal year{/ts}'{/localize}, 'greater_previous.fiscal_year', 'From end of previous fiscal year', NULL, 0, 0, 73, NULL, 0, 0, 1, NULL, NULL, NULL, NULL, NULL);