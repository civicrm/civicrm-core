{* placeholder file for upgrade*}

-- CRM-12470
UPDATE civicrm_financial_account
SET is_default = 1
WHERE name IN ('{ts escape="sql"}Premiums{/ts}', '{ts escape="sql"}Banking Fees{/ts}', '{ts escape="sql"}Accounts Payable{/ts}', '{ts escape="sql"}Donation{/ts}');
