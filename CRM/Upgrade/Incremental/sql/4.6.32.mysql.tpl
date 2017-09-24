{* file to handle db changes in 4.6.32 during upgrade *}

--CRM-21061 Increase report_id size from 64 to 512 to match civicrm_option_value.value column
ALTER TABLE civicrm_report_instance CHANGE COLUMN report_id report_id varchar(512) COMMENT 'FK to civicrm_option_value for the report template';
