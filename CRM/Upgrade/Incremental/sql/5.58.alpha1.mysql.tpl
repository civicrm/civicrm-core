{* file to handle db changes in 5.58.alpha1 during upgrade *}

-- dev/core#2611 Update data type for default_value on custom fields to TEXT
ALTER TABLE civicrm_custom_field MODIFY COLUMN default_value TEXT COMMENT 'Use form_options.is_default for field_types which use options.';
