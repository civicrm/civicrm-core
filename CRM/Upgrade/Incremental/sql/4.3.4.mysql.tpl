{* file to handle db changes in 4.3.4 during upgrade*}

-- CRM-12466
INSERT INTO
civicrm_option_group (name, {localize field='title'}title{/localize}, is_reserved, is_active)
VALUES
('contact_smart_group_display', {localize}'{ts escape="sql"}Contact Smart Group View Options{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_csgOpt := max(id) FROM civicrm_option_group WHERE name = 'contact_smart_group_display';

INSERT INTO
civicrm_option_value (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter,
is_default, weight)
VALUES
(@option_group_id_csgOpt, {localize}'Show Smart Groups on Demand'{/localize}, 1, 'showondemand', NULL, 0, 0, 1),
(@option_group_id_csgOpt, {localize}'Always Show Smart Groups'{/localize}, 2, 'alwaysshow', NULL, 0, 0, 2),
(@option_group_id_csgOpt, {localize}'Hide Smart Groups'{/localize}, 3, 'hide' , NULL, 0, 0, 3);


INSERT INTO civicrm_setting
(domain_id, contact_id, is_domain, group_name, name, value)
VALUES
({$domainID}, NULL, 1, 'CiviCRM Preferences', 'contact_smart_group_display', '{serialize}1{/serialize}');

-- CRM-12665 remove options groups
DELETE cov, cog FROM civicrm_option_group cog
INNER JOIN civicrm_option_value cov ON cov.option_group_id = cog.id
WHERE cog.name IN ('grant_program_status', 'allocation_algorithm');

-- CRM-12470
UPDATE civicrm_financial_account
SET is_default = 1
WHERE name IN ('Premiums', 'Banking Fees', 'Accounts Payable', 'Donation');

SELECT @option_group_id_arel := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_fat := max(id) from civicrm_option_group where name = 'financial_account_type';
SELECT @domainContactId := contact_id from civicrm_domain where id = {$domainID};

-- for Accounts Receivable Account is
SELECT @option_value_rel_id_ar  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Accounts Receivable Account is';
SELECT @arAccount := id FROM civicrm_financial_account WHERE name = 'accounts receivable';
SELECT @arAccountEntity := financial_account_id FROM civicrm_entity_financial_account
 WHERE account_relationship = @option_value_rel_id_ar AND entity_table = 'civicrm_financial_type' LIMIT 1;

INSERT INTO civicrm_entity_financial_account(entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_financial_type', cft.id, @option_value_rel_id_ar, IFNULL(@arAccount, @arAccountEntity)
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account ceft
ON ceft.entity_id = cft.id AND ceft.account_relationship = @option_value_rel_id_ar AND ceft.entity_table = 'civicrm_financial_type'
WHERE ceft.entity_id IS NULL;

-- for income account is
SELECT @option_value_rel_id  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Income Account is';
SELECT @opval := value FROM civicrm_option_value WHERE name = 'Revenue' and option_group_id = @option_group_id_fat;

-- create FA if not exists with same name as financial type
INSERT INTO civicrm_financial_account (name, contact_id, financial_account_type_id, description, account_type_code, is_active)
SELECT cft.name, @domainContactId, @opval, cft.name as description, 'INC', 1
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account ceft
ON ceft.entity_id = cft.id AND ceft.account_relationship = @option_value_rel_id AND ceft.entity_table = 'civicrm_financial_type'
LEFT JOIN civicrm_financial_account ca ON ca.name = cft.name
WHERE ceft.entity_id IS NULL AND ca.id IS NULL;

INSERT INTO civicrm_entity_financial_account(entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_financial_type', cft.id, @option_value_rel_id, ca.id
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account ceft
ON ceft.entity_id = cft.id AND ceft.account_relationship = @option_value_rel_id AND ceft.entity_table = 'civicrm_financial_type'
LEFT JOIN civicrm_financial_account ca ON ca.name = cft.name
WHERE ceft.entity_id IS NULL;

-- for cost of sales
SELECT @option_value_rel_id_cg := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Cost of Sales Account is';
SELECT @opCost := value FROM civicrm_option_value WHERE name = 'Cost of Sales' and option_group_id = @option_group_id_fat;
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

-- for Expense Account is
SELECT @option_value_rel_id_exp  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Expense Account is';
SELECT @opexp := value FROM civicrm_option_value WHERE name = 'Expenses' and option_group_id = @option_group_id_fat;
SET @financialAccountId := '';
SELECT @financialAccountId := id FROM civicrm_financial_account WHERE is_default = 1 and financial_account_type_id = @opexp;

-- CRM-13231
INSERT IGNORE INTO civicrm_financial_account (id, name, contact_id, financial_account_type_id, description, account_type_code, accounting_code, is_active, is_default)
VALUES (@financialAccountId, 'Banking Fees', @domainContactId, @opexp, 'Payment processor fees and manually recorded banking fees', 'EXP', '5200', 1, 1);

SELECT @financialAccountId := id FROM civicrm_financial_account WHERE is_default = 1 and financial_account_type_id = @opexp;


INSERT INTO civicrm_entity_financial_account(entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_financial_type', cft.id, @option_value_rel_id_exp, @financialAccountId
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account ceft
ON ceft.entity_id = cft.id AND ceft.account_relationship = @option_value_rel_id_exp AND ceft.entity_table = 'civicrm_financial_type'
WHERE ceft.entity_id IS NULL;
