{* file to handle db changes in 5.38.alpha1 during upgrade *}

ALTER TABLE civicrm_queue_item MODIFY data LONGTEXT;
