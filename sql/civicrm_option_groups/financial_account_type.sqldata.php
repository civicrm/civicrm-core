<?php
return CRM_Core_CodeGen_OptionGroup::create('financial_account_type', 'a/0070')
  ->addMetadata([
    'title' => ts('Financial Account Type'),
  ])
  ->addValueTable(['label', 'name', 'value', 'description'], [
    [ts('Asset'), 'Asset', 1, ts('Things you own'), 'is_reserved' => 1, 'component_id' => 2],
    [ts('Liability'), 'Liability', 2, ts('Things you owe, like a grant still to be disbursed'), 'is_reserved' => 1, 'component_id' => 2],
    [ts('Revenue'), 'Revenue', 3, ts('Income from contributions and sales of tickets and memberships'), 'is_default' => 1, 'is_reserved' => 1, 'component_id' => 2],
    [ts('Cost of Sales'), 'Cost of Sales', 4, ts('Costs incurred to get revenue, e.g. premiums for donations, dinner for a fundraising dinner ticket'), 'is_reserved' => 1, 'component_id' => 2],
    [ts('Expenses'), 'Expenses', 5, ts('Things that are paid for that are consumable, e.g. grants disbursed'), 'is_reserved' => 1, 'component_id' => 2],
  ]);
