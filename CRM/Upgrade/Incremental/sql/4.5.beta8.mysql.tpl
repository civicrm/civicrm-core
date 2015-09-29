{* file to handle db changes in 4.5.beta8 during upgrade *}

-- CRM-15143 Add Postal Code to contact reference and quick search options
SELECT @option_group_id_cao := max(id) from civicrm_option_group where name = 'contact_autocomplete_options';
SELECT @option_val_id_cao_wt := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cao;
SELECT @option_val_id_cao_val := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cao;
INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_cao, {localize}'{ts escape="sql"}Postal Code{/ts}'{/localize}, @option_val_id_cao_val+1, 'postal_code', NULL, 0, NULL, @option_val_id_cao_wt+1, 0, 1, 1, NULL, NULL);

SELECT @option_group_id_cro := max(id) from civicrm_option_group where name = 'contact_reference_options';
SELECT @option_val_id_cro_wt := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cro;
SELECT @option_val_id_cro_val := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cro;
INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_cro, {localize}'{ts escape="sql"}Postal Code{/ts}'{/localize}, @option_val_id_cro_val+1, 'postal_code', NULL, 0, NULL, @option_val_id_cro_wt+1, 0, 1, 1, NULL, NULL);
