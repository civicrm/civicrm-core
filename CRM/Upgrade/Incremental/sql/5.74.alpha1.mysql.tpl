{* file to handle db changes in 5.74.alpha1 during upgrade *}
UPDATE civicrm_navigation SET url = 'civicrm/import/participant?reset=1'
WHERE url = 'civicrm/event/import?reset=1';
