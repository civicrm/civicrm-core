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

-- CRM-13096
DROP TABLE IF EXISTS civicrm_official_receipt;

-- CRM-13231
SELECT @option_group_id_arel := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_fat := max(id) from civicrm_option_group where name = 'financial_account_type';
SELECT @opexp := value FROM civicrm_option_value WHERE name = 'Expenses' and option_group_id = @option_group_id_fat;
SELECT @financialAccountId := id FROM civicrm_financial_account WHERE is_default = 1 and financial_account_type_id = @opexp;
SELECT @domainContactId := contact_id from civicrm_domain where id = {$domainID};

SELECT @option_value_rel_id_exp  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Expense Account is';

INSERT IGNORE INTO civicrm_financial_account (id, name, contact_id, financial_account_type_id, description, account_type_code, accounting_code, is_active, is_default)
VALUES (@financialAccountId, 'Banking Fees', @domainContactId, @opexp, 'Payment processor fees and manually recorded banking fees', 'EXP', '5200', 1, 1);

SELECT @financialAccountId := id FROM civicrm_financial_account WHERE is_default = 1 and financial_account_type_id = @opexp;

INSERT INTO civicrm_entity_financial_account(entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_financial_type', cft.id, @option_value_rel_id_exp, @financialAccountId
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account ceft
ON ceft.entity_id = cft.id AND ceft.account_relationship = @option_value_rel_id_exp AND ceft.entity_table = 'civicrm_financial_type'
WHERE ceft.entity_id IS NULL;

UPDATE  civicrm_financial_trxn cft
INNER JOIN civicrm_entity_financial_trxn ceft ON ceft.financial_trxn_id = cft .id
INNER JOIN civicrm_entity_financial_trxn ceft1 ON ceft1.financial_trxn_id = cft .id
INNER JOIN civicrm_financial_item cfi ON cfi.id = ceft1.entity_id
INNER JOIN civicrm_contribution cc ON cc.id = ceft.entity_id
INNER JOIN civicrm_entity_financial_account cefa ON cefa.entity_id = cc.financial_type_id
SET cft.to_financial_account_id = cefa.financial_account_id
WHERE ceft.entity_table = 'civicrm_contribution' AND ceft1.entity_table = 'civicrm_financial_item' AND cfi.entity_table = 'civicrm_financial_trxn' AND cft.to_financial_account_id IS NULL AND cefa.entity_table = 'civicrm_financial_type' AND cefa.account_relationship = @option_value_rel_id_exp;

-- Add COGS account relationship
SELECT @option_value_rel_id_cg := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Cost of Sales Account is';
SELECT @opCost := value FROM civicrm_option_value WHERE name = 'Cost of Sales' and option_group_id = @option_group_id_fat;
SET @financialAccountId := '';
SELECT @financialAccountId := id FROM civicrm_financial_account WHERE is_default = 1 and financial_account_type_id = @opCost;

-- CRM-13231
INSERT IGNORE INTO civicrm_financial_account (id, name, contact_id, financial_account_type_id, description, account_type_code, accounting_code, is_active, is_default)
VALUES (@financialAccountId, 'Premiums', @domainContactId, @opCost, 'Account to record cost of premiums provided to payors', 'COGS', '5100', 1, 1);

SELECT @financialAccountId := id FROM civicrm_financial_account WHERE is_default = 1 and financial_account_type_id = @opCost;

INSERT INTO civicrm_entity_financial_account(entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_financial_type', cft.id, @option_value_rel_id_cg, @financialAccountId
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account ceft
ON ceft.entity_id = cft.id AND ceft.account_relationship = @option_value_rel_id_cg AND ceft.entity_table = 'civicrm_financial_type'
WHERE ceft.entity_id IS NULL;
