{* file to handle db changes in 5.23.alpha1 during upgrade *}
UPDATE civicrm_payment_processor SET is_default = 0 WHERE is_default IS NULL;
UPDATE civicrm_payment_processor SET is_active = 1 WHERE is_active IS NULL;
UPDATE civicrm_payment_processor SET is_test = 0 WHERE is_test IS NULL;
UPDATE civicrm_payment_processor_type SET is_active = 1 WHERE is_active IS NULL;
UPDATE civicrm_payment_processor_type SET is_default = 0 WHERE is_default IS NULL;
ALTER TABLE civicrm_payment_processor ALTER COLUMN is_default SET DEFAULT 0;
ALTER TABLE civicrm_payment_processor ALTER COLUMN is_active SET DEFAULT 1;
ALTER TABLE civicrm_payment_processor ALTER COLUMN is_test SET DEFAULT 0;
ALTER TABLE civicrm_payment_processor_type ALTER COLUMN is_active SET DEFAULT 1;
ALTER TABLE civicrm_payment_processor_type ALTER COLUMN is_default SET DEFAULT 0;


SELECT @option_group_id_date_filter    := max(id) from civicrm_option_group where name = 'relative_date_filters';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
   VALUES
   (@option_group_id_date_filter, {localize}'{ts escape="sql"}Last 6 months including today{/ts}'{/localize}, 'ending_6.month', 'ending_6.month', NULL, NULL, NULL,63, 0, 0, 1, NULL, NULL);