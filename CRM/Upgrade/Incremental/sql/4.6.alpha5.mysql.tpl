{* file to handle db changes in 4.6.alpha5 during upgrade *}

-- CRM-15910
ALTER TABLE `civicrm_contact`
  CHANGE COLUMN `external_identifier` `external_identifier` VARCHAR(64) DEFAULT NULL;
