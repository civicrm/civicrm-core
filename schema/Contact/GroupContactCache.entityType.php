<?php

return [
  'name' => 'GroupContactCache',
  'table' => 'civicrm_group_contact_cache',
  'class' => 'CRM_Contact_DAO_GroupContactCache',
  'getInfo' => fn() => [
    'title' => ts('Group Contact Cache'),
    'title_plural' => ts('Group Contact Caches'),
    'description' => ts('Join table cache for \'static\' groups.'),
    'add' => '2.1',
  ],
  'getIndices' => fn() => [
    'UI_contact_group' => [
      'fields' => [
        'contact_id' => TRUE,
        'group_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
  ],
  'getFields' => fn() => [
    'group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to civicrm_group'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Group'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_group',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to civicrm_contact'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
    ],
  ],
];
