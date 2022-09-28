<?php
// Adds option group for Afform.type

$mgd = [
  [
    'name' => 'AfformType',
    'entity' => 'OptionGroup',
    'params' => [
      'name' => 'afform_type',
      'title' => 'Afform Type',
    ],
  ],
  [
    'name' => 'AfformType:form',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'afform_type',
      'name' => 'form',
      'value' => 'form',
      'label' => 'Custom Form',
      'weight' => 0,
      'icon' => 'fa-list-alt',
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

try {
  $search = civicrm_api3('Extension', 'getsingle', [
    'full_name' => 'org.civicrm.search_kit',
  ]);
  if ($search['status'] === 'installed') {
    $mgd[] = [
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
    ];
  }
}
catch (Exception $e) {
  // ¯\_(ツ)_/¯
}

return $mgd;
