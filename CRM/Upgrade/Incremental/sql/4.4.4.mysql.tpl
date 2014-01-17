{* file to handle db changes in 4.4.4 during upgrade *}

{* update comment for civicrm_report_instance.grouprole *}
ALTER TABLE civicrm_report_instance MODIFY grouprole varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'role required to be able to run this instance';
