{* file to handle db changes in 4.4.beta1 during upgrade *}
ALTER TABLE civicrm_batch ADD data LONGTEXT NULL COMMENT 'cache entered data';
