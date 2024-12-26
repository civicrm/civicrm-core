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
    'id' => [
      'title' => ts('Group Contact Cache ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '2.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
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
      'entity_reference' => [
        'entity' => 'Group',
        'key' => 'id',
        'on_delete' => 'CASCADE',
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
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
