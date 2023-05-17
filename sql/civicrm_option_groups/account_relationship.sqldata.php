<?php
return CRM_Core_CodeGen_OptionGroup::create('account_relationship', 'a/0061')
  ->addMetadata([
    'title' => ts('Account Relationship'),
  ])
  ->addValueTable(['label', 'name', 'value', 'description'], [
    // TODO: Shouldn't we have ts() for these descriptions?
    [ts('Income Account is'), 'Income Account is', 1, 'Income Account is', 'is_default' => 1],
    [ts('Credit/Contra Revenue Account is'), 'Credit/Contra Revenue Account is', 2, 'Credit/Contra Revenue Account is'],
    [ts('Accounts Receivable Account is'), 'Accounts Receivable Account is', 3, 'Accounts Receivable Account is'],
    [ts('Credit Liability Account is'), 'Credit Liability Account is', 4, 'Credit Liability Account is', 'is_active' => 0],
    [ts('Expense Account is'), 'Expense Account is', 5, 'Expense Account is'],
    [ts('Asset Account is'), 'Asset Account is', 6, 'Asset Account is'],
    [ts('Cost of Sales Account is'), 'Cost of Sales Account is', 7, 'Cost of Sales Account is'],
    [ts('Premiums Inventory Account is'), 'Premiums Inventory Account is', 8, 'Premiums Inventory Account is'],
    [ts('Discounts Account is'), 'Discounts Account is', 9, 'Discounts Account is'],
    [ts('Sales Tax Account is'), 'Sales Tax Account is', 10, 'Sales Tax Account is'],
    [ts('Chargeback Account is'), 'Chargeback Account is', 11, 'Chargeback Account is'],
    [ts('Deferred Revenue Account is'), 'Deferred Revenue Account is', 12, 'Deferred Revenue Account is'],
  ])
  ->addDefaults([
    'component_id' => 2,
    'is_reserved' => 1,
  ]);
