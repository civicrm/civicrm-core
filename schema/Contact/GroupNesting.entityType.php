<?php

return [
  'name' => 'GroupNesting',
  'table' => 'civicrm_group_nesting',
  'class' => 'CRM_Contact_DAO_GroupNesting',
  'getInfo' => fn() => [
    'title' => ts('Group Nesting'),
    'title_plural' => ts('Group Nestings'),
    'description' => ts('Provide parent-child relationships for groups'),
    'log' => TRUE,
    'add' => '2.0',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Group Nesting ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Relationship ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'child_group_id' => [
      'title' => ts('Child Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of the child group'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Child Group'),
      ],
      'entity_reference' => [
        'entity' => 'Group',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'parent_group_id' => [
      'title' => ts('Parent Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of the parent group'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Parent Group'),
      ],
      'entity_reference' => [
        'entity' => 'Group',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
