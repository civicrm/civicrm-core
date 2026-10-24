<?php

use CRM_Utils_SQL_Literal as Literal;

return CRM_Core_CodeGen_OptionGroup::create('financial_item_status', 'a/0071')
  ->addMetadata([
    'title' => ts('Financial Item Status'),
    'is_locked' => 1,
  ])
  ->addValues([
    [
      'label' => ts('Paid'),
      'value' => 1,
      'name' => 'Paid',
      'weight' => 1,
      'description' => 'Paid',
      'is_reserved' => 1,
      'component_id' => new Literal('@componentCiviContribute'),
    ],
    [
      'label' => ts('Unpaid'),
      'value' => 3,
      'name' => 'Unpaid',
      'weight' => 1,
      'description' => 'Unpaid',
      'is_reserved' => 1,
      'component_id' => new Literal('@componentCiviContribute'),
    ],
    [
      'label' => ts('Partially paid'),
      'value' => 2,
      'name' => 'Partially paid',
      'weight' => 2,
      'description' => 'Partially paid',
      'is_reserved' => 1,
      'component_id' => new Literal('@componentCiviContribute'),
    ],
  ]);
