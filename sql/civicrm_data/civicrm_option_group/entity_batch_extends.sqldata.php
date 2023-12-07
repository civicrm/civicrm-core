<?php
return CRM_Core_CodeGen_OptionGroup::create('entity_batch_extends', 'a/0083')
  ->addMetadata([
    'title' => ts('Entity Batch Extends'),
  ])
  ->addValues([
    [
      'label' => ts('Financial Transactions'),
      'value' => 'civicrm_financial_trxn',
      'name' => 'civicrm_financial_trxn',
      'is_default' => 1,
      'component_id' => 2,
    ],
  ]);
