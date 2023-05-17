<?php
return CRM_Core_CodeGen_OptionGroup::create('entity_batch_extends', 'a/0083')
  ->addMetadata([
    'title' => ts('Entity Batch Extends'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Financial Transactions'), 'civicrm_financial_trxn', 'civicrm_financial_trxn', 'is_default' => 1, 'component_id' => 2],
  ]);
