-- CRM-10810
SELECT @max_strict := max(id), @cnt_strict := count(*) FROM `civicrm_dedupe_rule_group` WHERE `contact_type` = 'Individual' AND `level` = 'Strict' AND `is_default` = 1;
UPDATE `civicrm_dedupe_rule_group` SET  `is_default` = 0 WHERE @cnt_strict > 1 AND id = @max_strict;

SELECT @max_fuzzy := max(id), @cnt_fuzzy := count(*) FROM `civicrm_dedupe_rule_group` WHERE `contact_type` = 'Individual' AND `level` = 'Fuzzy' AND `is_default` = 1;
UPDATE `civicrm_dedupe_rule_group` SET  `is_default` = 0 WHERE @cnt_fuzzy > 1 AND id = @max_fuzzy;

-- Insert line items for contribution for api/import  
SELECT @fieldID := cpf.id, @fieldValueID := cpfv.id FROM civicrm_price_set cps
LEFT JOIN civicrm_price_field cpf ON  cps.id = cpf.price_set_id
LEFT JOIN civicrm_price_field_value cpfv ON cpf.id = cpfv.price_field_id
WHERE cps.name = 'default_contribution_amount';

INSERT INTO civicrm_line_item ( entity_table, entity_id, price_field_id,label, qty, unit_price, line_total, participant_count, price_field_value_id )
SELECT 'civicrm_contribution', cc.id, @fieldID, 'Contribution Amount', 1, total_amount, total_amount , 0, @fieldValueID
FROM `civicrm_contribution` cc
LEFT JOIN civicrm_line_item cli ON cc.id=cli.entity_id and cli.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_membership_payment cmp ON cc.id = cmp.contribution_id
LEFT JOIN civicrm_participant_payment cpp ON cc.id = cpp.contribution_id
WHERE cli.entity_id IS NULL AND cc.contribution_page_id IS NULL AND cmp.contribution_id IS NULL AND cpp.contribution_id IS NULL
GROUP BY cc.id;
