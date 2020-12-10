{* file to handle db changes in 5.20.alpha1 during upgrade *}

UPDATE civicrm_navigation SET url = "civicrm/api3" WHERE url = "civicrm/api" AND domain_id = {$domainID};
