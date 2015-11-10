{* file to handle db changes in 4.7.beta1 during upgrade *}

-- CRM-16901 Recurring contributions summary report template
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_report, {localize}'{ts escape="sql"}Recurring Contributions Summary{/ts}'{/localize}, 'contribute/recursummary', 'CRM_Report_Form_Contribute_RecurSummary',               NULL, 0, NULL, 49, {localize}'{ts escape="sql"}Provides simple summary for each payment instrument for which there are recurring contributions (e.g. Standing Order and Direct Debit), showing within a given date range.{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL);