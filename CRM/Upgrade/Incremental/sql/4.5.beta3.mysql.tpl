{* file to handle db changes in 4.5.beta3 during upgrade *}
--CRM-15009
ALTER TABLE `civicrm_payment_processor`
CHANGE COLUMN `signature` `signature` LONGTEXT NULL DEFAULT NULL;
