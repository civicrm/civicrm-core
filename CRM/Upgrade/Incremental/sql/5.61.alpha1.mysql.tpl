{* file to handle db changes in 5.61.alpha1 during upgrade *}

{* https://github.com/civicrm/civicrm-core/pull/25873 *}
UPDATE civicrm_payment_processor
  SET {localize field="frontend_title,title"}frontend_title = COALESCE(title, name){/localize};

UPDATE civicrm_payment_processor
  SET {localize field="title"}title = name{/localize};

{* https://github.com/civicrm/civicrm-core/pull/25994 *}
UPDATE civicrm_campaign c1, civicrm_campaign c2
  SET c2.name = CONCAT(c2.name, '_', c2.id)
  WHERE c2.name = c1.name AND c2.id > c1.id;

UPDATE civicrm_navigation SET url = 'civicrm/import/contribution?reset=1' WHERE url = 'civicrm/contribute/import?reset=1';
