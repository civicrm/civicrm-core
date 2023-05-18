<?php
return CRM_Core_CodeGen_OptionGroup::create('communication_style', 'a/0074')
  ->addMetadata([
    'title' => ts('Communication Style'),
  ])
  ->addValues([
    [
      'label' => ts('Formal'),
      'value' => 1,
      'name' => 'formal',
      'is_default' => 1,
    ],
    [
      'label' => ts('Familiar'),
      'value' => 2,
      'name' => 'familiar',
    ],
  ]);
