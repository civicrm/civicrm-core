{* file to handle db changes in 6.22.alpha1 during upgrade *}

DELETE `cpt` FROM `civicrm_payment_token` `cpt`
  INNER JOIN `civicrm_payment_processor` `cpp` ON `cpt`.`payment_processor_id` = `cpp`.`id`
  INNER JOIN `civicrm_payment_processor_type` `cppt` ON `cpp`.`payment_processor_type_id` = `cppt`.`id`
  WHERE `cppt`.`name` = 'FirstData';

DELETE `cpp` FROM `civicrm_payment_processor` `cpp`
  INNER JOIN `civicrm_payment_processor_type` `cppt` ON `cpp`.`payment_processor_type_id` = `cppt`.`id`
  WHERE `cppt`.`name` = 'FirstData';

DELETE FROM `civicrm_payment_processor_type`
  WHERE `name` = 'FirstData';
