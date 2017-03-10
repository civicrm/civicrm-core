{* file to handle db changes in 4.7.10 during upgrade *}
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
SELECT @option_group_id_report_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
INSERT INTO
   civicrm_option_value (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
   (@option_group_id_report, {localize}'{ts escape="sql"}Deferred Revenue Details{/ts}'{/localize}, 'contribute/deferredrevenue', 'CRM_Report_Form_Contribute_DeferredRevenue', NULL, 0, NULL, @option_group_id_report_wt+1, {localize}'{ts escape="sql"}Deferred Revenue Details Report{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL);

-- CRM-18854
ALTER TABLE civicrm_pledge_block ADD pledge_start_date varchar(64) NULL DEFAULT NULL COMMENT 'The date that the first scheduled pledge occurs.';
ALTER TABLE civicrm_pledge_block ADD is_pledge_start_date_visible TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'If true - recurring start date is shown.';
ALTER TABLE civicrm_pledge_block ADD is_pledge_start_date_editable TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'If true - recurring start date is editable.';
ALTER TABLE civicrm_contribution_page ADD adjust_recur_start_date TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'If true - user is able to adjust payment start date.' AFTER is_recur_installments;

-- CRM-17608 Merge to DOCx or ODT template
SELECT @option_group_id_ext := max(id) from civicrm_option_group where name = 'safe_file_extension';
SELECT @option_group_id_ext_wt  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_ext;
SELECT @option_group_id_ext_val := MAX(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @option_group_id_ext;
INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`)
VALUES
   (@option_group_id_ext, {localize}'{ts escape="sql"}odt{/ts}'{/localize}, @option_group_id_ext_val+1, 'odt', NULL, 0, 0, @option_group_id_ext_wt+1, 0, 1, 1);
