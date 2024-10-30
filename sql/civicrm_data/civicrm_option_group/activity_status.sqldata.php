<?php
return CRM_Core_CodeGen_OptionGroup::create('activity_status', 'a/0024')
  ->addMetadata([
    'title' => ts('Activity Status'),
    'data_type' => 'Integer',
    'option_value_fields' => 'name,label,description,color',
  ])
  ->addValues([
    [
      'label' => ts('Scheduled'),
      'value' => 1,
      'name' => 'Scheduled',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Completed'),
      'value' => 2,
      'name' => 'Completed',
      'filter' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Cancelled'),
      'value' => 3,
      'name' => 'Cancelled',
      'filter' => 2,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Left Message'),
      'value' => 4,
      'name' => 'Left Message',
    ],
    [
      'label' => ts('Unreachable'),
      'value' => 5,
      'name' => 'Unreachable',
      'filter' => 2,
    ],
    [
      'label' => ts('Not Required'),
      'value' => 6,
      'name' => 'Not Required',
      'filter' => 2,
    ],
    [
      'label' => ts('Available'),
      'value' => 7,
      'name' => 'Available',
    ],
    [
      'label' => ts('No-show'),
      'value' => 8,
      'name' => 'No_show',
      'filter' => 2,
    ],
  ]);
