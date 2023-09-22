{* file to handle db changes in 5.49.alpha1 during upgrade *}

UPDATE `civicrm_contact_type` SET `icon` = 'fa-user' WHERE `name` = 'Individual';
UPDATE `civicrm_contact_type` SET `icon` = 'fa-home' WHERE `name` = 'Household';
UPDATE `civicrm_contact_type` SET `icon` = 'fa-building' WHERE `name` = 'Organization';
