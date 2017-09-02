{* file to handle db changes in 4.7.25 during upgrade *}

--CRM-21061 Increase report_id size from 64 to 512 to match civicrm_option_value.value column
ALTER TABLE civicrm_report_instance CHANGE COLUMN report_id report_id varchar(512) COMMENT 'FK to civicrm_option_value for the report template';

--CRM-20892 Add a last_modified field to prevent cross-editing between browser instances
ALTER TABLE `civicrm_mailing`
ADD COLUMN `last_modified` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Who last modified this mailing?';


