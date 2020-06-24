{* file to handle db changes in 5.25.alpha1 during upgrade *}

-- dev/core#1569 Update data type for form values to LONGTEXT
ALTER TABLE civicrm_report_instance MODIFY COLUMN form_values LONGTEXT COMMENT 'Submitted form values for this report';
