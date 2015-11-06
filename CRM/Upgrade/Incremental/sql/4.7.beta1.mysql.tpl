{* file to handle db changes in 4.7.beta1 during upgrade *}

-- CRM-16901 Recurring contribution tab in display preference
SELECT @option_group_id_cvOpt := max(id) FROM civicrm_option_group WHERE name = 'contact_view_options';
SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op  WHERE op.option_group_id  = @option_group_id_cvOpt;
SELECT @max_wt := ROUND(val.weight) FROM civicrm_option_value val WHERE val.option_group_id = @option_group_id_cvOpt AND val.name = 'CiviContribute';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_cvOpt, {localize}'{ts escape="sql"}Recurring Contribution{/ts}'{/localize}, @max_val+1, 'CiviContributeRecur', NULL, 0, NULL,  @max_wt+1, 0, 0, 1, NULL, NULL);

-- CRM-16901 Manual Payment processor type
INSERT INTO `civicrm_payment_processor_type`
    (name, title, description, is_active, is_default, user_name_label, password_label, signature_label, subject_label, class_name, url_site_default, url_api_default, url_recur_default, url_button_default, url_site_test_default, url_api_test_default, url_recur_test_default, url_button_test_default, billing_mode, is_recur )
VALUES
    ('Manual', '{ts escape="sql"}Manual Payment Processor{/ts}',NULL,1,1,'{ts escape="sql"}User Name{/ts}',NULL,NULL,NULL,'Payment_Manual',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1);

-- CRM-16901 Recurring contributions summary report template
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_report, {localize}'{ts escape="sql"}Recurring Contributions Summary{/ts}'{/localize}, 'contribute/recursummary', 'CRM_Report_Form_Contribute_RecurSummary',               NULL, 0, NULL, 49, {localize}'{ts escape="sql"}Provides simple summary for each payment instrument for which there are recurring contributions (e.g. Standing Order and Direct Debit), showing within a given date range.{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL);