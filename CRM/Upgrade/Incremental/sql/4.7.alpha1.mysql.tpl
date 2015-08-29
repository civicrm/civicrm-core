{* fields to support self-service cancel or transfer for 4.7.1 *}
ALTER TABLE civicrm_event ADD selfcancelxfer_time INT(10) NULL DEFAULT 0;
ALTER TABLE civicrm_event ADD allow_selfcancelxfer BOOLEAN NULL DEFAULT 0;
