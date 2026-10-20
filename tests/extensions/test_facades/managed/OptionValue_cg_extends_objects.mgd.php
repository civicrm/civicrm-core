<?php
use CRM_TestFacades_ExtensionUtil as E;

// Enables custom fields for TestThing, and separately for its TestWidget facade.
// Both share the table civicrm_test_thing, so match on value rather than name.
return [
  [
    'name' => 'cg_extend_objects:TestThing',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Test Things'),
        'value' => 'TestThing',
        'name' => 'civicrm_test_thing',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'value',
        'option_group_id',
      ],
    ],
  ],
  [
    'name' => 'cg_extend_objects:TestWidget',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Test Widgets'),
        'value' => 'TestWidget',
        'name' => 'civicrm_test_thing',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'value',
        'option_group_id',
      ],
    ],
  ],
];
