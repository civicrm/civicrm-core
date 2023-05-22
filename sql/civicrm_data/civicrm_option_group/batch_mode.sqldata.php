<?php
return CRM_Core_CodeGen_OptionGroup::create('batch_mode', 'a/0065')
  ->addMetadata([
    'title' => ts('Batch Mode'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'description'], [
    // TODO: Shouldn't we have ts() for these descriptions?
    [ts('Manual Batch'), 'Manual Batch', 1, 'Manual Batch'],
    [ts('Automatic Batch'), 'Automatic Batch', 2, 'Automatic Batch'],
  ])
  ->addDefaults([
    'is_reserved' => 1,
    'component_id' => 2,
  ]);
