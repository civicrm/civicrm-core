{* file to handle db changes in 4.7.beta4 during upgrade *}

-- Add flag for existing payments

UPDATE civicrm_financial_trxn ft INNER JOIN
  (SELECT financial_account_id FROM civicrm_entity_financial_account efa INNER JOIN civicrm_option_value v ON efa.account_relationship = v.value AND v.name = 'Asset Account is'
    INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'account_relationship' GROUP BY financial_account_id)
    AS asset_fa ON ft.to_financial_account_id = asset_fa.financial_account_id SET ft.is_payment = TRUE;
