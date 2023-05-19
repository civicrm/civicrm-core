<?php
return CRM_Core_CodeGen_OptionGroup::create('batch_type', 'a/0064')
  ->addMetadata([
    'title' => ts('Batch Type'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Contribution'), 'Contribution', 1],
    [ts('Membership'), 'Membership', 2],
    [ts('Pledge Payment'), 'Pledge Payment', 3],
  ]);
