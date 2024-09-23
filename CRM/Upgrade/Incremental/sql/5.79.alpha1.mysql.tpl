{* file to handle db changes in 5.79.alpha1 during upgrade *}
UPDATE `civicrm_financial_type` SET {localize field="label"}`label` = `name`{/localize};
UPDATE `civicrm_financial_account` SET {localize field="label"}`label` = `name`{/localize};
