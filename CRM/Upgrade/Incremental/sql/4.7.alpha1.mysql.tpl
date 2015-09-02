{* fields to support self-service cancel or transfer for 4.7.1 *}
ALTER TABLE `civicrm_event` ADD COLUMN `selfcancelxfer_time` INT(10) unsigned DEFAULT 0 COMMENT 'Time in hours before start datetime to cancel or transfer;
ALTER TABLE `civicrm_event` ADD COLUMN `allow_selfcancelxfer` TINYINT(4) DEFAULT '0' COMMENT 'Does event allow self service update';
