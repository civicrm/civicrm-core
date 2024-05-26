{* file to handle db changes in 5.74.alpha1 during upgrade *}
UPDATE civicrm_navigation SET url = 'civicrm/import/membership?reset=1'
WHERE url = 'civicrm/member/import?reset=1';
