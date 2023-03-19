{* file to handle db changes in 5.61.alpha1 during upgrade *}

{* https://github.com/civicrm/civicrm-core/pull/25873 *}
UPDATE civicrm_payment_processor
  SET {localize field="frontend_title,title"}frontend_title = COALESCE(title, name){/localize};

UPDATE civicrm_payment_processor
  SET {localize field="title"}title = name{/localize};
