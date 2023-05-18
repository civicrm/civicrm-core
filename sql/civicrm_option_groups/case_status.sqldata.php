<?php
return CRM_Core_CodeGen_OptionGroup::create('case_status', 'a/0026')
  ->addMetadata([
    'title' => ts('Case Status'),
    'option_value_fields' => 'name,label,description,color',
  ])
  ->addValues([
    [
      'label' => ts('Ongoing'),
      'value' => 1,
      'name' => 'Open',
      'grouping' => 'Opened',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Resolved'),
      'value' => 2,
      'name' => 'Closed',
      'grouping' => 'Closed',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Urgent'),
      'value' => 3,
      'name' => 'Urgent',
      'grouping' => 'Opened',
    ],
  ]);
