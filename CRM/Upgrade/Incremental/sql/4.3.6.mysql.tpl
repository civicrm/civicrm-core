{* file to handle db changes in 4.3.6 during upgrade *}
-- CRM-13060
UPDATE civicrm_price_set_entity cpse
LEFT JOIN civicrm_price_set cps ON cps.id = cpse.price_set_id
LEFT JOIN civicrm_price_field cpf ON cps.id = cpf.price_set_id
LEFT JOIN civicrm_price_field_value cpfv ON cpf.id = cpfv.price_field_id
LEFT JOIN civicrm_event ce ON cpse.entity_id = ce.id AND cpse.entity_table = 'civicrm_event'
LEFT JOIN civicrm_contribution_page ccg ON cpse.entity_id = ccg.id AND cpse.entity_table = 'civicrm_contribution_page'
SET cpfv.financial_type_id = CASE 
  WHEN cpfv.membership_type_id IS NULL AND ce.id IS NOT NULL
    THEN ce.financial_type_id
  WHEN cpfv.membership_type_id IS NULL AND ccg.id IS NOT NULL
   THEN ccg.financial_type_id
  ELSE cpfv.financial_type_id
END,
cps.financial_type_id = CASE 
  WHEN ce.id IS NOT NULL
    THEN ce.financial_type_id
  WHEN ccg.id IS NOT NULL
    THEN ccg.financial_type_id
END
WHERE cps.is_quick_config = 1;