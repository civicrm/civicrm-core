<?php
return CRM_Core_CodeGen_OptionGroup::create('financial_account_type', 'a/0070')
  ->addMetadata([
    'title' => ts('Financial Account Type'),
  ])
  ->addValues([
    [
      'label' => ts('Asset'),
      'value' => 1,
      'name' => 'Asset',
      'description' => ts('Things you own'),
      'is_reserved' => 1,
      'component_id' => 2,
    ],
    [
      'label' => ts('Liability'),
      'value' => 2,
      'name' => 'Liability',
      'description' => ts('Things you owe, like a grant still to be disbursed'),
      'is_reserved' => 1,
      'component_id' => 2,
    ],
    [
      'label' => ts('Revenue'),
      'value' => 3,
      'name' => 'Revenue',
      'is_default' => 1,
      'description' => ts('Income from contributions and sales of tickets and memberships'),
      'is_reserved' => 1,
      'component_id' => 2,
    ],
    [
      'label' => ts('Cost of Sales'),
      'value' => 4,
      'name' => 'Cost of Sales',
      'description' => ts('Costs incurred to get revenue, e.g. premiums for donations, dinner for a fundraising dinner ticket'),
      'is_reserved' => 1,
      'component_id' => 2,
    ],
    [
      'label' => ts('Expenses'),
      'value' => 5,
      'name' => 'Expenses',
      'description' => ts('Things that are paid for that are consumable, e.g. grants disbursed'),
      'is_reserved' => 1,
      'component_id' => 2,
    ],
  ]);
