{* file to handle db changes in 4.3.6 during upgrade *}
-- CRM-13060
UPDATE civicrm_price_set_entity cpse
LEFT JOIN civicrm_price_set cps ON cps.id = cpse.price_set_id
LEFT JOIN civicrm_price_field cpf ON cps.id = cpf.price_set_id
LEFT JOIN civicrm_price_field_value cpfv ON cpf.id = cpfv.price_field_id
LEFT JOIN civicrm_event ce ON cpse.entity_id = ce.id AND cpse.entity_table = 'civicrm_event'
LEFT JOIN civicrm_contribution_page ccg ON cpse.entity_id = ccg.id AND cpse.entity_table = 'civicrm_contribution_page'
SET cpfv.financial_type_id = CASE 
  WHEN ce.id IS NOT NULL
    THEN ce.financial_type_id
  WHEN ccg.id IS NOT NULL
    THEN ccg.financial_type_id
END,
cps.financial_type_id = CASE 
  WHEN ce.id IS NOT NULL
    THEN ce.financial_type_id
  WHEN ccg.id IS NOT NULL
    THEN ccg.financial_type_id
END
WHERE cps.is_quick_config = 1;

-- CRM-12844
-- DELETE bad data
DELETE cli FROM `civicrm_contribution` cc
LEFT JOIN civicrm_line_item cli ON cli.entity_id = cc.id 
LEFT JOIN civicrm_financial_item cfi ON cfi.entity_id = cli.id AND cfi.entity_table = 'civicrm_line_item' 
LEFT JOIN civicrm_price_field cpf ON cpf.id = cli.price_field_id
LEFT JOIN civicrm_price_set cps ON cps.id = cpf.price_set_id
WHERE cc.contribution_recur_id IS NOT NULL 
AND cli.entity_table = 'civicrm_contribution' AND cfi.id IS NULL
AND cps.is_quick_config = 1;

-- Set from_financial_account_id to null
UPDATE `civicrm_contribution` cc
LEFT JOIN civicrm_entity_financial_trxn ceft ON ceft.entity_id = cc.id
LEFT JOIN civicrm_financial_trxn cft ON cft.id = ceft.financial_trxn_id
LEFT JOIN civicrm_entity_financial_trxn ceft1 ON ceft1.financial_trxn_id = ceft.financial_trxn_id 
LEFT JOIN civicrm_financial_item cfi ON cfi.id = ceft1.entity_id
LEFT JOIN civicrm_entity_financial_account cefa ON cefa.entity_id = cft.payment_processor_id
SET cft.from_financial_account_id = NULL
WHERE ceft.entity_table = 'civicrm_contribution'  AND cc.contribution_recur_id IS NOT NULL
AND ceft1.entity_table = 'civicrm_financial_item' AND cft.id IS NOT NULL AND cft.payment_instrument_id = 1 AND cfi.entity_table = 'civicrm_line_item' AND cft.from_financial_account_id IS NOT NULL
AND cefa.entity_table = 'civicrm_payment_processor' AND cefa.financial_account_id = cft.to_financial_account_id;
