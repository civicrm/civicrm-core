<?php

return [
  'name' => 'GroupOrganization',
  'table' => 'civicrm_group_organization',
  'class' => 'CRM_Contact_DAO_GroupOrganization',
  'getInfo' => fn() => [
    'title' => ts('Group Organization'),
    'title_plural' => ts('Group Organizations'),
    'description' => ts('Integrate Organization information into Groups'),
    'log' => TRUE,
    'add' => '2.0',
  ],
  'getIndices' => fn() => [
    'UI_group_organization' => [
      'fields' => [
        'group_id' => TRUE,
        'organization_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Group Organization ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Relationship ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('ID of the group'),
      'add' => '2.0',
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
    'organization_id' => [
      'title' => ts('Organization ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of the Organization Contact'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Organization'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
