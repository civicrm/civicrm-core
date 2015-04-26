{* file to handle db changes in 4.6.3 during upgrade *}

-- CRM-16356
ALTER TABLE `civicrm_setting` ADD UNIQUE (name);
