<?php
return CRM_Core_CodeGen_OptionGroup::create('note_privacy', 'a/0050')
  ->addMetadata([
    'title' => ts('Privacy levels for notes'),
  ])
  ->addValues([
    [
      'label' => ts('None'),
      'value' => 0,
      'name' => 'None',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Author Only'),
      'value' => 1,
      'name' => 'Author Only',
      'is_reserved' => 1,
    ],
  ]);
