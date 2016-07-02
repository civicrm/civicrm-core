{* file to handle db changes in 4.7.10 during upgrade *}
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
SELECT @option_group_id_report_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
INSERT INTO
   civicrm_option_value (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
   (@option_group_id_report, {localize}'{ts escape="sql"}{/ts}'{/localize}, 'contribute/tiralBalance', 'CRM_Report_Form_Contribute_DeferredRevenue', NULL, 0, NULL, @option_group_id_report_wt+1, {localize}'{ts escape="sql"}Deferred Revenue Details Report{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL);
