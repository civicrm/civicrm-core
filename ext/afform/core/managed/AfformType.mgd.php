<?php
// Adds option group for Afform.type

$mgd = [
  [
    'name' => 'AfformType',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'name' => 'afform_type',
      'title' => 'Afform Type',
      'option_value_fields' => ['name', 'label', 'icon', 'description'],
    ],
  ],
  [
    'name' => 'AfformType:form',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'afform_type',
      'name' => 'form',
      'value' => 'form',
      'label' => 'Submission Form',
      'weight' => 0,
      'icon' => 'fa-list-alt',
    ],
  ],
  [
    'name' => 'AfformType:search',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'afform_type',
      'name' => 'search',
      'value' => 'search',
      'label' => 'Search Form',
      'weight' => 10,
      'icon' => 'fa-search',
    ],
  ],
  [
    'name' => 'AfformType:block',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'afform_type',
      'name' => 'block',
      'value' => 'block',
      'label' => 'Field Block',
      'weight' => 20,
      'icon' => 'fa-th-large',
    ],
  ],
  [
    'name' => 'AfformType:system',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'afform_type',
      'name' => 'system',
      'value' => 'system',
      'label' => 'System Form',
      'weight' => 50,
      'icon' => 'fa-lock',
    ],
  ],
];

return $mgd;
