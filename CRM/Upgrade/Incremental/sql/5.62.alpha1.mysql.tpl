{* file to handle db changes in 5.62.alpha1 during upgrade *}

UPDATE `civicrm_setting` SET `domain_id` = NULL WHERE `name` = 'enable_components' AND `domain_id` = {$domainID};
DELETE FROM `civicrm_setting` WHERE `name` = 'enable_components' AND `domain_id` IS NOT NULL;
