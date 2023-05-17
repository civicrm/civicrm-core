<?php
return CRM_Core_CodeGen_OptionGroup::create('financial_item_status', 'a/0071')
  ->addMetadata([
    'title' => ts('Financial Item Status'),
    'is_locked' => 1,
  ])
  ->addValues(['label', 'name', 'value', 'weight', 'description'], [
    // TODO: Shouldn't we have ts() for these descriptions?
    [ts('Paid'), 'Paid', 1, 1, 'Paid', 'is_reserved' => 1, 'component_id' => 2],
    [ts('Unpaid'), 'Unpaid', 3, 1, 'Unpaid', 'is_reserved' => 1, 'component_id' => 2],
    [ts('Partially paid'), 'Partially paid', 2, 2, 'Partially paid', 'is_reserved' => 1, 'component_id' => 2],
  ]);
