<?php
return CRM_Core_CodeGen_OptionGroup::create('activity_status', 'a/0024')
  ->addMetadata([
    'title' => ts('Activity Status'),
    'data_type' => 'Integer',
    'option_value_fields' => 'name,label,description,color',
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Scheduled'), 'Scheduled', 1, 'is_default' => 1, 'is_reserved' => 1],
    [ts('Completed'), 'Completed', 2, 'filter' => 1, 'is_reserved' => 1],
    [ts('Cancelled'), 'Cancelled', 3, 'filter' => 2, 'is_reserved' => 1],
    [ts('Left Message'), 'Left Message', 4],
    [ts('Unreachable'), 'Unreachable', 5, 'filter' => 2],
    [ts('Not Required'), 'Not Required', 6, 'filter' => 2],
    [ts('Available'), 'Available', 7],
    [ts('No-show'), 'No_show', 8, 'filter' => 2],
  ]);
