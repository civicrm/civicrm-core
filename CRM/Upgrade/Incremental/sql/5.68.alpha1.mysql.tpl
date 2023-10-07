{* file to handle db changes in 5.68.alpha1 during upgrade *}

UPDATE `civicrm_tag` SET `label` = `name` WHERE `label` = '';
