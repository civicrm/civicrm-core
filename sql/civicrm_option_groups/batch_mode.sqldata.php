<?php
return CRM_Core_CodeGen_OptionGroup::create('batch_mode', 'a/0065')
  ->addMetadata([
    'title' => ts('Batch Mode'),
    'is_locked' => 1,
  ])
  ->addValues(['label', 'name', 'value', 'description'], [
    [ts('Manual Batch'), 'Manual Batch', 1, ts('Manual Batch'), 'is_reserved' => 1, 'component_id' => 2],
    [ts('Automatic Batch'), 'Automatic Batch', 2, ts('Automatic Batch'), 'is_reserved' => 1, 'component_id' => 2],
  ]);
