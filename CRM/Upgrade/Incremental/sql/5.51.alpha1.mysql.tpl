{* file to handle db changes in 5.51.alpha1 during upgrade *}
ALTER TABLE `civicrm_mailing_bounce_type` CHANGE `name` `name` VARCHAR(256) NOT NULL COMMENT 'Type of bounce', CHANGE `description` `description` VARCHAR(2048) NULL DEFAULT NULL COMMENT 'A description of this bounce type';
