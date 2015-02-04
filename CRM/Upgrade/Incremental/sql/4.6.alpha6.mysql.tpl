{* file to handle db changes in 4.6.alpha6 during upgrade *}

UPDATE `civicrm_navigation` SET url = 'civicrm/api' WHERE url = 'civicrm/api/explorer';