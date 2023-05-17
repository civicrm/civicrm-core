<?php
return CRM_Core_CodeGen_OptionGroup::create('account_relationship', 'a/0061')
  ->addMetadata([
    'title' => ts('Account Relationship'),
  ])
  ->addValues(['label', 'name', 'value', 'description'], [
    [ts('Income Account is'), 'Income Account is', 1, ts('Income Account is'), 'is_default' => 1],
    [ts('Credit/Contra Revenue Account is'), 'Credit/Contra Revenue Account is', 2, ts('Credit/Contra Revenue Account is')],
    [ts('Accounts Receivable Account is'), 'Accounts Receivable Account is', 3, ts('Accounts Receivable Account is')],
    [ts('Credit Liability Account is'), 'Credit Liability Account is', 4, ts('Credit Liability Account is'), 'is_active' => 0],
    [ts('Expense Account is'), 'Expense Account is', 5, ts('Expense Account is')],
    [ts('Asset Account is'), 'Asset Account is', 6, ts('Asset Account is')],
    [ts('Cost of Sales Account is'), 'Cost of Sales Account is', 7, ts('Cost of Sales Account is')],
    [ts('Premiums Inventory Account is'), 'Premiums Inventory Account is', 8, ts('Premiums Inventory Account is')],
    [ts('Discounts Account is'), 'Discounts Account is', 9, ts('Discounts Account is')],
    [ts('Sales Tax Account is'), 'Sales Tax Account is', 10, ts('Sales Tax Account is')],
    [ts('Chargeback Account is'), 'Chargeback Account is', 11, ts('Chargeback Account is')],
    [ts('Deferred Revenue Account is'), 'Deferred Revenue Account is', 12, ts('Deferred Revenue Account is')],
  ])
  ->addDefaults([
    'component_id' => 2,
    'is_reserved' => 1,
  ]);
