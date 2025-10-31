<?php

return [
  'name' => 'GroupContact',
  'table' => 'civicrm_group_contact',
  'class' => 'CRM_Contact_DAO_GroupContact',
  'getInfo' => fn() => [
    'title' => ts('Group Contact'),
    'title_plural' => ts('Group Contacts'),
    'description' => ts('Join table sets membership for \'static\' groups. Also used to store \'opt-out\' entries for \'query\' type groups (status = \'OUT\')'),
    'log' => TRUE,
    'add' => '1.1',
  ],
  'getIndices' => fn() => [
    'UI_contact_group' => [
      'fields' => [
        'contact_id' => TRUE,
        'group_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.6',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Group Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
      'usage' => [
        'import',
        'export',
      ],
    ],
    'group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to civicrm_group'),
      'add' => '1.1',
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
      'usage' => [
        'import',
        'export',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to civicrm_contact'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
      'usage' => [
        'import',
        'export',
      ],
    ],
    'status' => [
      'title' => ts('Group Contact Status'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('status of contact relative to membership in group'),
      'add' => '1.1',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'groupContactStatus'],
      ],
      'usage' => [
        'import',
        'export',
      ],
    ],
    'location_id' => [
      'title' => ts('Location ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Optional location to associate with this membership'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Location'),
      ],
      'entity_reference' => [
        'entity' => 'LocBlock',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'email_id' => [
      'title' => ts('Email ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Optional email to associate with this membership'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Email'),
      ],
      'entity_reference' => [
        'entity' => 'Email',
        'key' => 'id',
      ],
    ],
  ],
];
